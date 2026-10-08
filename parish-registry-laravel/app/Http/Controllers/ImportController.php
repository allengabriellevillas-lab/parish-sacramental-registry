<?php

namespace App\Http\Controllers;

use App\Models\{ImportBatch, ImportStagedRow, Person, SacramentalRecord};
use App\Services\SacramentalRecordValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function store(Request $request, SacramentalRecordValidator $validator)
    {
        if (! $request->hasFile('file') || $request->file('file')->getSize() > 5 * 1024 * 1024 || ! preg_match('/\.csv$/i', $request->file('file')->getClientOriginalName())) {
            return response()->json(['error' => 'Upload a CSV no larger than 5MB'], 422);
        }

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            $raw = array_combine($headers, array_pad($values, count($headers), ''));
            [, $errors] = $validator->validate($raw);
            $rows[] = ['row' => count($rows) + 2, 'data' => $raw, 'status' => $errors ? 'Invalid' : 'Valid', 'errors' => $errors];
        }
        fclose($handle);

        $batch = ImportBatch::create([
            'parish_id' => $request->user()->parish_id,
            'filename' => basename($request->file('file')->getClientOriginalName()),
            'uploaded_by' => $request->user()->id,
            'total_rows' => count($rows),
            'valid_rows' => count(array_filter($rows, fn ($row) => $row['status'] === 'Valid')),
        ]);

        foreach ($rows as $row) {
            ImportStagedRow::create(['batch_id' => $batch->id, 'row_number' => $row['row'], 'payload' => $row['data'], 'validation_errors' => $row['errors']]);
        }

        return response()->json(['data' => ['batch_id' => $batch->id, 'rows' => $rows], 'meta' => ['total' => count($rows), 'valid' => $batch->valid_rows]], 201);
    }

    public function commit(Request $request, ImportBatch $batch, SacramentalRecordValidator $validator)
    {
        abort_unless((int) $batch->parish_id === (int) $request->user()->parish_id, 404);
        if ($batch->status !== 'staged') {
            return response()->json(['error' => 'Staged batch not found'], 404);
        }

        $count = DB::transaction(function () use ($batch, $validator, $request) {
            $count = 0;
            foreach ($batch->rows as $row) {
                [$fields, $errors] = $validator->validate($row->payload);
                if ($errors) {
                    continue;
                }

                $person = Person::firstOrCreate([
                    'first_name' => $fields['first_name'],
                    'last_name' => $fields['last_name'],
                    'date_of_birth' => $fields['date_of_birth'] ?: null,
                ], $fields);

                SacramentalRecord::create([
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
                    'source' => 'bulk_import',
                    'created_by' => $request->user()->id,
                ]);
                $count++;
            }

            $batch->update(['committed_rows' => $count, 'status' => 'committed']);
            return $count;
        });

        return response()->json(['data' => ['committed_rows' => $count]]);
    }

    public function destroy(Request $request, ImportBatch $batch)
    {
        abort_unless((int) $batch->parish_id === (int) $request->user()->parish_id, 404);
        if ($batch->status === 'staged') {
            $batch->update(['status' => 'discarded']);
        }
        return response()->json(['data' => true]);
    }

    public function template()
    {
        return response()->download(storage_path('app/parish_bulk_import_template.csv'), 'parish_bulk_import_template.csv', ['Content-Type' => 'text/csv']);
    }
}
