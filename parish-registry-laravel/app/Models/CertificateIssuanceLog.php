<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CertificateIssuanceLog extends Model { protected $table='certificate_issuance_logs'; const CREATED_AT='issued_at'; const UPDATED_AT=null; protected $fillable=['sacramental_record_id','requestor_name','purpose','issued_by','pdf_path']; public function record(){return $this->belongsTo(SacramentalRecord::class,'sacramental_record_id');} public function issuer(){return $this->belongsTo(User::class,'issued_by');} }
