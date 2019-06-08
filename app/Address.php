<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
  protected $hidden = [
        'created_at', 'updated_at',
    ];
	protected $table ='address';
}
