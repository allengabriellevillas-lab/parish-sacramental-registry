<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CertificateTemplate extends Model { protected $table='certificate_templates'; protected $primaryKey='sacrament_type'; public $incrementing=false; protected $keyType='string'; const CREATED_AT=null; const UPDATED_AT='updated_at'; protected $fillable=['title_text','body_template','footer_note']; }
