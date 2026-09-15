<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    protected $fillable = [
        'tracking_code',
        'reference_code',
        'sacrament_type',
        'status',
        'requester_name',
        'requestor_name',
        'requestor_email',
        'requestor_phone',
        'relationship',
        'subject_name',
        'subject_approx_date',
        'supporting_doc_path',
        'requester_email',
        'requester_phone',
        'relationship_to_person',
        'purpose',
        'delivery_method',
        'person_first_name',
        'person_middle_name',
        'person_last_name',
        'person_gender',
        'person_date_of_birth',
        'father_name',
        'mother_maiden_name',
        'spouse_name',
        'event_date',
        'event_year',
        'notes',
        'public_note',
        'staff_notes',
        'attachment_path',
        'attachment_original_name',
        'sacramental_record_id',
        'status_updated_by',
        'submitted_at',
    ];

    protected $casts = [
        'person_date_of_birth' => 'date',
        'event_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(SacramentalRecord::class, 'sacramental_record_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(CertificateRequestStatusLog::class);
    }

    public function statusUpdater()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }
}
