<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\SacramentalRecord;
use App\Services\SacramentalRecordValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    private function shape(SacramentalRecord $record): array
    {
        $person = $record->person;
        return [
            'id' => $record->id,
            'sacrament' => $record->sacrament_type,
            'eventDate' => $record->event_date?->format('Y-m-d'),
            'book' => $record->book_number,
            'page' => $record->page_number,
            'line' => $record->line_number,
            'minister' => $record->minister_name,
            'sponsors' => $record->sponsors,
            'marginNotes' => $record->margin_notes,
            'firstName' => $person->first_name,
            'middleName' => $person->middle_name,
            'lastName' => $person->last_name,
            'gender' => $person->gender,
            'dob' => $person->date_of_birth?->format('Y-m-d'),
            'fatherName' => $person->father_name,
            'motherName' => $person->mother_maiden_name,
            'spouse' => $person->spouse_name,
        ];
    }

    public function index(Request $request)
    {
        $query = (string) $request->query('q', '');
        $rows = SacramentalRecord::with('person')
            ->where('parish_id', $request->user()->parish_id)
            ->whereHas('person', fn ($person) => $person
                ->where('first_name', 'like', "%$query%")
                ->orWhere('middle_name', 'like', "%$query%")
                ->orWhere('last_name', 'like', "%$query%")
                ->orWhere('date_of_birth', 'like', "%$query%"))
            ->orderByDesc('event_date')
            ->get();

        return response()->json(['data' => $rows->map(fn ($row) => $this->shape($row))->values(), 'meta' => [
            'total' => $rows->count(),
            'page' => 1,
            'per_page' => min(100, max(1, (int) $request->query('per_page', 25))),
        ]]);
    }

    public function show(Request $request, SacramentalRecord $record)
    {
        abort_unless((int) $record->parish_id === (int) $request->user()->parish_id, 404);
        $record->load('person');
        return response()->json(['data' => $this->shape($record)]);
    }

    public function store(Request $request, SacramentalRecordValidator $validator)
    {
        [$fields, $errors] = $validator->validate($request->all());
        if ($errors) {
            return response()->json(['error' => 'Validation failed', 'errors' => $errors], 422);
        }

        $record = DB::transaction(function () use ($fields, $request) {
            $person = Person::firstOrCreate([
                'first_name' => $fields['first_name'],
                'last_name' => $fields['last_name'],
                'date_of_birth' => $fields['date_of_birth'] ?: null,
            ], $fields);

            return SacramentalRecord::create([
                'parish_id' => $request->user()->parish_id,
                'person_id' => $person->id,
                'sacrament_type' => $fields['sacrament_type'],
                'event_date' => $fields['event_date'],
                'book_number' => $fields['book_number'],
                'page_number' => $fields['page_number'],
                'line_number' => $fields['line_number'],
                'minister_name' => $fields['minister_name'] ?: null,
                'sponsors' => $fields['sponsors'] ?: null,
                'margin_notes' => $fields['margin_notes'] ?: null,
                'created_by' => $request->user()->id,
            ])->load('person');
        });

        return response()->json(['data' => $this->shape($record), 'meta' => [
            'next_book_number' => $fields['book_number'],
            'next_line_number' => (string) ((int) $fields['line_number'] + 1),
            'dedupe_match' => false,
        ]], 201);
    }
}
