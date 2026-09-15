<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportStagedRow extends Model { public $timestamps=false; protected $table='import_staged_rows'; protected $fillable=['batch_id','row_number','payload','validation_errors']; protected $casts=['payload'=>'array','validation_errors'=>'array']; }
