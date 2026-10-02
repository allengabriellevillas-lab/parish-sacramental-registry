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
        $dataUri = static function ($path): ?string {
            if (! $path) return null;

            $disk = Storage::disk('public');
            if ($disk->exists($path)) {
                $mime = $disk->mimeType($path);
                $contents = $disk->get($path);
            } elseif (preg_match('#^uploads/[A-Za-z0-9_.-]+$#', $path) && is_file(base_path('../' . $path))) {
                $legacyPath = base_path('../' . $path);
                $mime = mime_content_type($legacyPath);
                $contents = file_get_contents($legacyPath);
            } else {
                return null;
            }

            if (! in_array($mime, ['image/png', 'image/jpeg'], true) || $contents === false) return null;

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        };
        // The certificate seal must come from the seal field in Parish Settings.
        $sealDataUri = $dataUri($parish->seal_image_path);

        $response = Pdf::loadView('certificates.certificate', [
            'parish' => $parish,
            'record' => $record,
            'title' => $template->title_text,
            'body' => $body,
            'footerNote' => $template->footer_note,
            'priestName' => $record->minister_name ?: $parish->default_priest_name,
            'today' => now()->format('F j, Y'),
            'issuedTo' => $log->requestor_name === 'Not specified' ? '____________________' : $log->requestor_name,
            'purpose' => $log->purpose,
            'logoDataUri' => $dataUri($parish->logo_image_path),
            'sealDataUri' => $sealDataUri,
            'signatureDataUri' => $dataUri($parish->priest_signature_path),
        ])->setPaper('A4', 'portrait')->stream('certificate-' . $record->id . '.pdf');

        // A certificate URL can be reopened with the same issuance log after
        // branding changes. Do not let the browser reuse the previous PDF.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
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
