<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    
   // protected $table='server';
	 public function IPS()
    {
        //return $this->hasmany('App\IP');
    }
    public function coinName(){
        return $this->belongsTo('App\Coins','coin_id');
    }
    public function coordinates(){
        return $this->hasMany('App\Coordinate','dist_id');
    }
	
}
