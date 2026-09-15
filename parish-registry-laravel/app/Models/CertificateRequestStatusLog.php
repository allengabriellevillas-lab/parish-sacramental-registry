<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequestStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'certificate_request_id',
        'status',
        'note',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(CertificateRequest::class, 'certificate_request_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
