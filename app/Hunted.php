<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Hunted extends Model
{
  protected $hidden = ["created_at", "updated_at","deleted_at"];
  protected $table='hunted';
  use SoftDeletes; 
}
