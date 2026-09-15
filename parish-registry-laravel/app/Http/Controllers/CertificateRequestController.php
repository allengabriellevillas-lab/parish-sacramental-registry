<?php

namespace App\Http\Controllers;

use App\Models\{CertificateIssuanceLog, CertificateRequest, SacramentalRecord};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CertificateRequestController extends Controller
{
    private array $sacraments = ['Baptism', 'Communion', 'Confirmation', 'Marriage', 'Death'];
    private array $statuses = ['submitted', 'under_review', 'needs_more_info', 'approved', 'ready_for_pickup', 'released', 'rejected', 'not_found'];
    private array $delivery = ['pickup', 'email_copy', 'courier'];

    private function clean(mixed $value): ?string
    {
        $value = trim(strip_tags((string) $value));
        return $value === '' ? null : $value;
    }

    private function trackingCode(): string
    {
        do {
            $code = 'PCR-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (CertificateRequest::where('tracking_code', $code)->exists());

        return $code;
    }

    private function shape(CertificateRequest $request, bool $details = false): array
    {
        $record = $request->record;
        $person = $record?->person;
        $data = [
            'id' => $request->id,
            'tracking_code' => $request->tracking_code,
            'status' => $request->status,
            'sacrament_type' => $request->sacrament_type,
            'requester_name' => $request->requester_name,
            'requester_email' => $request->requester_email,
            'requester_phone' => $request->requester_phone,
            'relationship_to_person' => $request->relationship_to_person,
            'purpose' => $request->purpose,
            'delivery_method' => $request->delivery_method,
            'person_first_name' => $request->person_first_name,
            'person_middle_name' => $request->person_middle_name,
            'person_last_name' => $request->person_last_name,
            'person_gender' => $request->person_gender,
            'person_date_of_birth' => $request->person_date_of_birth?->format('Y-m-d'),
            'father_name' => $request->father_name,
            'mother_maiden_name' => $request->mother_maiden_name,
            'spouse_name' => $request->spouse_name,
            'event_date' => $request->event_date?->format('Y-m-d'),
            'event_year' => $request->event_year,
            'notes' => $request->notes,
            'public_note' => $request->public_note,
            'staff_notes' => $request->staff_notes,
            'has_attachment' => (bool) $request->attachment_path,
            'attachment_original_name' => $request->attachment_original_name,
            'sacramental_record_id' => $request->sacramental_record_id,
            'submitted_at' => $request->submitted_at?->toDateTimeString(),
            'updated_at' => $request->updated_at?->toDateTimeString(),
            'record' => $record ? [
                'id' => $record->id,
                'sacrament' => $record->sacrament_type,
                'eventDate' => $record->event_date?->format('Y-m-d'),
                'book' => $record->book_number,
                'page' => $record->page_number,
                'line' => $record->line_number,
                'personName' => trim($person->first_name . ' ' . $person->middle_name . ' ' . $person->last_name),
            ] : null,
        ];

        if ($details) {
            $data['status_logs'] = $request->statusLogs->map(fn ($log) => [
                'status' => $log->status,
                'note' => $log->note,
                'changed_by' => $log->changedBy?->full_name,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->values();
        }

        return $data;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sacrament_type' => 'required|in:' . implode(',', $this->sacraments),
            'requester_name' => 'required|string|max:200',
            'requester_email' => 'nullable|email|max:150',
            'requester_phone' => 'required|string|max:50',
            'relationship_to_person' => 'nullable|string|max:100',
            'purpose' => 'required|string|max:150',
            'delivery_method' => 'required|in:' . implode(',', $this->delivery),
            'person_first_name' => 'required|string|max:100',
            'person_middle_name' => 'nullable|string|max:100',
            'person_last_name' => 'required|string|max:100',
            'person_gender' => 'nullable|in:Male,Female,Unknown',
            'person_date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:200',
            'mother_maiden_name' => 'nullable|string|max:200',
            'spouse_name' => 'nullable|string|max:200',
            'event_date' => 'nullable|date',
            'event_year' => 'nullable|digits:4',
            'notes' => 'nullable|string|max:4000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Please check the request details.', 'errors' => $validator->errors()->all()], 422);
        }

        $tracking = $this->trackingCode();
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('certificate-requests/' . $tracking, 'local');
            $attachmentName = $file->getClientOriginalName();
        }

        $payload = [
                'tracking_code' => $tracking,
                // Older installations used reference_code as their required
                // public identifier. Keep it in sync with the tracking code.
                'reference_code' => $tracking,
                'sacrament_type' => $this->clean($request->input('sacrament_type')),
                'requester_name' => $this->clean($request->input('requester_name')),
                'requester_email' => $this->clean($request->input('requester_email')),
                'requester_phone' => $this->clean($request->input('requester_phone')),
                'relationship_to_person' => $this->clean($request->input('relationship_to_person')),
                'purpose' => $this->clean($request->input('purpose')),
                'delivery_method' => $this->clean($request->input('delivery_method')) ?: 'pickup',
                'person_first_name' => $this->clean($request->input('person_first_name')),
                'person_middle_name' => $this->clean($request->input('person_middle_name')),
                'person_last_name' => $this->clean($request->input('person_last_name')),
                'person_gender' => $this->clean($request->input('person_gender')) ?: 'Unknown',
                'person_date_of_birth' => $this->clean($request->input('person_date_of_birth')),
                'father_name' => $this->clean($request->input('father_name')),
                'mother_maiden_name' => $this->clean($request->input('mother_maiden_name')),
                'spouse_name' => $this->clean($request->input('spouse_name')),
                'event_date' => $this->clean($request->input('event_date')),
                'event_year' => $this->clean($request->input('event_year')),
                'notes' => $this->clean($request->input('notes')),
                'attachment_path' => $attachmentPath,
                'attachment_original_name' => $attachmentName,
        ];

        // Some existing installations have the original public-request schema.
        // Only write these fields when that schema is present, so new installs
        // retain the current, richer request structure without extra columns.
        $columns = array_flip(Schema::getColumnListing('certificate_requests'));
        $legacy = [
            'requestor_name' => $payload['requester_name'],
            'requestor_email' => $payload['requester_email'] ?? '',
            'requestor_phone' => $payload['requester_phone'],
            'relationship' => $payload['relationship_to_person'] ?? 'Self',
            'subject_name' => trim(implode(' ', array_filter([
                $payload['person_first_name'],
                $payload['person_middle_name'],
                $payload['person_last_name'],
            ]))),
            'subject_approx_date' => $payload['event_date'] ?? $payload['person_date_of_birth'],
            'supporting_doc_path' => $attachmentPath,
        ];

        foreach ($legacy as $column => $value) {
            if (isset($columns[$column])) {
                $payload[$column] = $value;
            }
        }

        $certificateRequest = DB::transaction(function () use ($payload) {
            $row = CertificateRequest::create($payload);
            $row->statusLogs()->create(['status' => 'submitted', 'note' => 'Request submitted through the public portal.']);
            return $row;
        });

        return response()->json(['data' => [
            'tracking_code' => $certificateRequest->tracking_code,
            'status' => $certificateRequest->status,
            'submitted_at' => $certificateRequest->submitted_at?->toDateTimeString(),
        ]], 201);
    }

    public function track(Request $request)
    {
        $code = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string) $request->query('tracking_code', $request->query('code', ''))));
        $row = CertificateRequest::with('statusLogs')
            ->where('tracking_code', $code)
            ->orWhere('reference_code', $code)
            ->first();

        if (! $row) {
            return response()->json(['error' => 'Request not found. Check the tracking code and try again.'], 404);
        }

        return response()->json(['data' => [
            'tracking_code' => $row->tracking_code,
            'status' => $row->status,
            'sacrament_type' => $row->sacrament_type,
            'person_name' => trim($row->person_first_name . ' ' . $row->person_middle_name . ' ' . $row->person_last_name),
            'public_note' => $row->public_note,
            'submitted_at' => $row->submitted_at?->toDateTimeString(),
            'updated_at' => $row->updated_at?->toDateTimeString(),
            'status_logs' => $row->statusLogs->map(fn ($log) => [
                'status' => $log->status,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->values(),
        ]]);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));
        $counts = CertificateRequest::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $rows = CertificateRequest::with(['record.person'])
            ->when(in_array($status, $this->statuses, true), fn ($x) => $x->where('status', $status))
            ->when($status === 'open', fn ($x) => $x->whereIn('status', ['submitted', 'under_review', 'needs_more_info', 'approved']))
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('tracking_code', 'like', "%$q%")
                    ->orWhere('requester_name', 'like', "%$q%")
                    ->orWhere('requester_email', 'like', "%$q%")
                    ->orWhere('requester_phone', 'like', "%$q%")
                    ->orWhere('person_first_name', 'like', "%$q%")
                    ->orWhere('person_last_name', 'like', "%$q%");
            }))
            ->latest('submitted_at')
            ->get();

        return response()->json(['data' => $rows->map(fn ($row) => $this->shape($row))->values(), 'meta' => [
            'total' => $rows->count(),
            'counts' => [
                'all' => (int) $counts->sum(),
                'open' => (int) collect(['submitted', 'under_review', 'needs_more_info', 'approved'])->sum(fn ($status) => $counts[$status] ?? 0),
                ...collect($this->statuses)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all(),
            ],
        ]]);
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $certificateRequest->load(['record.person', 'statusLogs.changedBy']);
        return response()->json(['data' => $this->shape($certificateRequest, true)]);
    }

    public function update(Request $request, CertificateRequest $certificateRequest)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', $this->statuses),
            'sacramental_record_id' => 'nullable|integer|exists:sacramental_records,id',
            'public_note' => 'nullable|string|max:4000',
            'staff_notes' => 'nullable|string|max:4000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Please check the request update.', 'errors' => $validator->errors()->all()], 422);
        }

        $oldStatus = $certificateRequest->status;
        $certificateRequest->update([
            'status' => $request->input('status'),
            'sacramental_record_id' => $request->input('sacramental_record_id') ?: null,
            'public_note' => $this->clean($request->input('public_note')),
            'staff_notes' => $this->clean($request->input('staff_notes')),
            'status_updated_by' => $request->user()->id,
        ]);

        if ($oldStatus !== $certificateRequest->status) {
            $certificateRequest->statusLogs()->create([
                'status' => $certificateRequest->status,
                'note' => $certificateRequest->staff_notes,
                'changed_by' => $request->user()->id,
            ]);
        }

        $certificateRequest->load(['record.person', 'statusLogs.changedBy']);
        return response()->json(['data' => $this->shape($certificateRequest, true)]);
    }

    public function attachment(CertificateRequest $certificateRequest)
    {
        if (! $certificateRequest->attachment_path || ! Storage::disk('local')->exists($certificateRequest->attachment_path)) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        $name = $certificateRequest->attachment_original_name ?: basename($certificateRequest->attachment_path);
        return response(Storage::disk('local')->get($certificateRequest->attachment_path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($certificateRequest->attachment_path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
        ]);
    }

    public function matches(CertificateRequest $certificateRequest)
    {
        $lastName = $certificateRequest->person_last_name;
        $firstName = $certificateRequest->person_first_name;
        $dob = $certificateRequest->person_date_of_birth?->format('Y-m-d');
        $eventYear = $certificateRequest->event_date?->format('Y') ?: $certificateRequest->event_year;

        $rows = SacramentalRecord::with('person')
            ->where('sacrament_type', $certificateRequest->sacrament_type)
            ->whereHas('person', function ($q) use ($lastName, $firstName, $dob) {
                $q->where('last_name', 'like', "%$lastName%")
                    ->orWhere('first_name', 'like', "%$firstName%");
                if ($dob) {
                    $q->orWhere('date_of_birth', $dob);
                }
            })
            ->get()
            ->map(function ($record) use ($certificateRequest, $lastName, $firstName, $dob, $eventYear) {
                $person = $record->person;
                $score = 0;
                $score += strcasecmp($person->last_name, $lastName) === 0 ? 40 : (stripos($person->last_name, $lastName) !== false ? 20 : 0);
                $score += strcasecmp($person->first_name, $firstName) === 0 ? 25 : (stripos($person->first_name, $firstName) !== false ? 10 : 0);
                $score += $dob && $person->date_of_birth?->format('Y-m-d') === $dob ? 25 : 0;
                $score += $eventYear && $record->event_date?->format('Y') === $eventYear ? 10 : 0;

                return [
                    'id' => $record->id,
                    'score' => $score,
                    'sacrament' => $record->sacrament_type,
                    'eventDate' => $record->event_date?->format('Y-m-d'),
                    'book' => $record->book_number,
                    'page' => $record->page_number,
                    'line' => $record->line_number,
                    'personName' => trim($person->first_name . ' ' . $person->middle_name . ' ' . $person->last_name),
                    'dob' => $person->date_of_birth?->format('Y-m-d'),
                    'parents' => trim(($person->father_name ?: 'Father not listed') . ' / ' . ($person->mother_maiden_name ?: 'Mother not listed')),
                ];
            })
            ->sortByDesc('score')
            ->take(10)
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function claimSlip(CertificateRequest $certificateRequest)
    {
        $certificateRequest->load('record.person');

        return Pdf::loadView('certificate_requests.claim-slip', [
            'request' => $certificateRequest,
            'statusLabel' => ucwords(str_replace('_', ' ', $certificateRequest->status)),
            'today' => now()->format('F j, Y'),
        ])->setPaper('letter', 'portrait')->stream('request-' . $certificateRequest->tracking_code . '-claim-slip.pdf');
    }

    public function issue(Request $request, CertificateRequest $certificateRequest)
    {
        if (! $certificateRequest->sacramental_record_id) {
            return response()->json(['error' => 'Link a matching sacramental record before issuing a certificate.'], 422);
        }

        $record = SacramentalRecord::findOrFail($certificateRequest->sacramental_record_id);
        $log = DB::transaction(function () use ($request, $certificateRequest, $record) {
            $log = CertificateIssuanceLog::create([
                'sacramental_record_id' => $record->id,
                'requestor_name' => $certificateRequest->requester_name,
                'purpose' => $certificateRequest->purpose,
                'issued_by' => $request->user()->id,
            ]);
            $certificateRequest->update([
                'status' => 'ready_for_pickup',
                'status_updated_by' => $request->user()->id,
            ]);
            $certificateRequest->statusLogs()->create([
                'status' => 'ready_for_pickup',
                'note' => 'Certificate generated from staff request queue.',
                'changed_by' => $request->user()->id,
            ]);
            return $log;
        });

        return response()->json(['data' => [
            'id' => $certificateRequest->id,
            'status' => 'ready_for_pickup',
            'record_id' => $record->id,
            'log_id' => $log->id,
        ]], 201);
    }
}
