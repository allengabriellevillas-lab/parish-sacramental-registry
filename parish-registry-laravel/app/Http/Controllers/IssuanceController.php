<?php

namespace App\Http\Controllers;

use App\Models\{CertificateIssuanceLog, SacramentalRecord};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IssuanceController extends Controller
{
    public function store(Request $request, SacramentalRecord $record)
    {
        $name = trim(strip_tags((string) $request->input('requestor_name')));
        $purpose = trim(strip_tags((string) $request->input('purpose')));
        if (!$name || !$purpose) return response()->json(['error' => 'Requestor name and purpose are required'], 422);

        $log = CertificateIssuanceLog::create(['sacramental_record_id' => $record->id, 'requestor_name' => $name, 'purpose' => $purpose, 'issued_by' => $request->user()->id]);
        return response()->json(['data' => ['id' => $log->id, 'sacramental_record_id' => $record->id, 'requestor_name' => $name, 'purpose' => $purpose]], 201);
    }

    public function certificate(Request $request, SacramentalRecord $record)
    {
        $log = CertificateIssuanceLog::whereKey($request->query('log_id'))->where('sacramental_record_id', $record->id)->first();
        if (!$log) return response()->json(['error' => 'Issuance log required'], 404);

        $record->load('person');
        // Fetch these records for every PDF, so a Settings save affects the next certificate.
        $parish = SettingsController::parish();
        $template = SettingsController::template($record->sacrament_type);
        $person = $record->person;
        $strong = fn ($value) => "<strong style='color:#101010;'>" . e($value) . '</strong>';
        $body = strtr(e($template->body_template), [
            '{name}' => $strong(trim($person->first_name . ' ' . $person->middle_name . ' ' . $person->last_name)),
            '{dob}' => $strong($person->date_of_birth?->format('F j, Y') ?? '—'),
            '{fatherName}' => $strong($person->father_name),
            '{motherName}' => $strong($person->mother_maiden_name),
            '{eventDate}' => $strong($record->event_date->format('F j, Y')),
            '{spouse}' => $strong($person->spouse_name),
            '{sacrament}' => $strong($record->sacrament_type),
            '{book}' => e($record->book_number),
            '{page}' => e($record->page_number),
            '{line}' => e($record->line_number),
        ]);
        $dataUri = fn ($path) => $path && Storage::disk('public')->exists($path)
            ? 'data:' . Storage::disk('public')->mimeType($path) . ';base64,' . base64_encode(Storage::disk('public')->get($path))
            : null;

        return Pdf::loadView('certificates.certificate', [
            'parish' => $parish,
            'record' => $record,
            'title' => $template->title_text,
            'body' => $body,
            'footerNote' => $template->footer_note,
            'priestName' => $record->minister_name ?: $parish->default_priest_name,
            'today' => now()->format('F j, Y'),
            'issuedTo' => $log->requestor_name === 'Not specified' ? '____________________' : $log->requestor_name,
            'purpose' => $log->purpose,
            'sealDataUri' => $dataUri($parish->seal_image_path),
            'signatureDataUri' => $dataUri($parish->priest_signature_path),
        ])->setPaper('letter', 'portrait')->stream('certificate-' . $record->id . '.pdf');
    }

    public function index(Request $request)
    {
        $logs = CertificateIssuanceLog::with(['record.person', 'issuer'])->latest('issued_at')->get()->map(fn ($log) => [
            'id' => $log->id,
            'issuedTo' => $log->requestor_name,
            'purpose' => $log->purpose,
            'timestamp' => $log->issued_at,
            'issuedBy' => $log->issuer->full_name,
            'sacrament' => $log->record->sacrament_type,
            'personName' => trim($log->record->person->first_name . ' ' . $log->record->person->middle_name . ' ' . $log->record->person->last_name),
        ]);

        return response()->json(['data' => $logs, 'meta' => ['total' => $logs->count()]]);
    }
}
