<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParishSetting extends Model { protected $table='parish_settings'; public $incrementing=false; const CREATED_AT=null; const UPDATED_AT='updated_at'; protected $fillable=['parish_name','diocese_name','address','seal_image_path','default_priest_name','priest_signature_path']; }
