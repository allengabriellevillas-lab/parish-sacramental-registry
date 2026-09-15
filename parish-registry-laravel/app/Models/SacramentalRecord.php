<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SacramentalRecord extends Model { protected $table='sacramental_records'; protected $fillable=['person_id','sacrament_type','event_date','book_number','page_number','line_number','minister_name','sponsors','margin_notes','source','created_by']; protected $casts=['event_date'=>'date']; public function person(){return $this->belongsTo(Person::class);} public function issuanceLogs(){return $this->hasMany(CertificateIssuanceLog::class);} public function creator(){return $this->belongsTo(User::class,'created_by');} }
