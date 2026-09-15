<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Person extends Model { protected $table='persons'; protected $fillable=['first_name','middle_name','last_name','gender','date_of_birth','father_name','mother_maiden_name','spouse_name']; protected $casts=['date_of_birth'=>'date']; public function records(){return $this->hasMany(SacramentalRecord::class);} }
