<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportBatch extends Model { protected $table='import_batches'; const CREATED_AT='uploaded_at'; const UPDATED_AT=null; protected $fillable=['filename','uploaded_by','total_rows','valid_rows','committed_rows','status']; public function rows(){return $this->hasMany(ImportStagedRow::class,'batch_id');} }
