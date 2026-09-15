<html>
<head>
    <style>
        @page { size: letter; margin: .6in; }
        body { font-family: DejaVu Sans, sans-serif; color: #151b2b; font-size: 12px; margin: 0; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: .8px; color: #5e1f29; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #c3cbd9; padding: 9px 10px; vertical-align: top; }
        .muted { color: #64738c; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 3px; }
        .code { font-family: DejaVu Sans Mono, monospace; font-size: 18px; font-weight: bold; }
        .header { border-bottom: 3px solid #a9812f; padding-bottom: 15px; margin-bottom: 18px; }
        .badge { display: inline-block; border: 1px solid #a9812f; color: #8a6a24; padding: 4px 8px; border-radius: 20px; font-weight: bold; }
        .sign { margin-top: 42px; width: 260px; border-top: 1px solid #151b2b; padding-top: 6px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Certificate Request Claim Slip</h1>
        <span class="muted">Tracking Code</span>
        <div class="code">{{ $request->tracking_code }}</div>
        <p class="badge">{{ $statusLabel }}</p>
    </div>

    <table>
        <tr>
            <td><span class="muted">Requester</span>{{ $request->requester_name }}</td>
            <td><span class="muted">Contact</span>{{ $request->requester_phone }} @if($request->requester_email)<br>{{ $request->requester_email }}@endif</td>
        </tr>
        <tr>
            <td><span class="muted">Certificate</span>{{ $request->sacrament_type }}</td>
            <td><span class="muted">Purpose</span>{{ $request->purpose }}</td>
        </tr>
        <tr>
            <td><span class="muted">Release Option</span>{{ ucwords(str_replace('_', ' ', $request->delivery_method)) }}</td>
            <td><span class="muted">Submitted</span>{{ optional($request->submitted_at)->format('F j, Y g:i A') }}</td>
        </tr>
    </table>

    <h2>Record Details Provided</h2>
    <table>
        <tr>
            <td><span class="muted">Name</span>{{ trim($request->person_first_name.' '.$request->person_middle_name.' '.$request->person_last_name) }}</td>
            <td><span class="muted">Birth Date</span>{{ optional($request->person_date_of_birth)->format('F j, Y') ?: 'Not supplied' }}</td>
        </tr>
        <tr>
            <td><span class="muted">Father</span>{{ $request->father_name ?: 'Not supplied' }}</td>
            <td><span class="muted">Mother</span>{{ $request->mother_maiden_name ?: 'Not supplied' }}</td>
        </tr>
        <tr>
            <td><span class="muted">Sacrament Date</span>{{ optional($request->event_date)->format('F j, Y') ?: ($request->event_year ?: 'Not supplied') }}</td>
            <td><span class="muted">Spouse</span>{{ $request->spouse_name ?: 'Not applicable / not supplied' }}</td>
        </tr>
    </table>

    @if($request->record)
        <h2>Matched Register Entry</h2>
        <table>
            <tr>
                <td><span class="muted">Person</span>{{ trim($request->record->person->first_name.' '.$request->record->person->middle_name.' '.$request->record->person->last_name) }}</td>
                <td><span class="muted">Event Date</span>{{ optional($request->record->event_date)->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td><span class="muted">Book / Page / Line</span>Book {{ $request->record->book_number }} / Page {{ $request->record->page_number }} / Line {{ $request->record->line_number }}</td>
                <td><span class="muted">Minister</span>{{ $request->record->minister_name ?: 'Not listed' }}</td>
            </tr>
        </table>
    @endif

    @if($request->public_note)
        <h2>Public Note</h2>
        <p>{{ $request->public_note }}</p>
    @endif

    <p style="margin-top: 28px;">Please present this slip and a valid ID when claiming the certificate. This slip is not a sacramental certificate.</p>
    <div class="sign">Received / Released By</div>
    <p style="margin-top: 28px; color:#64738c; font-size: 10px;">Printed {{ $today }}</p>
</body>
</html>
