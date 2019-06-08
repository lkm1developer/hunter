<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
class Coins extends Model
{
	protected $table= 'coins';
	protected $hidden = ["created_at", "updated_at", 'active','py_name'];
    public function masternode()
    {
        return $this->hasOne('App\usermasternode','masternode_id');
    }
	public function MNS()
    {
        return $this->hasmany('App\usermasternode','masternode_id')->where('step',5);
    }
    public function balance()
    {
        return $this->hasOne('App\Wallet','coin_id')->where('bal','>',0)->where('user_id',Auth::id());
    }
    public function Meta()
    {
        return $this->hasOne('App\Coinsmeta');
    }
    public function Available()
    {
        return $this->hasmany('App\Coordinate','coin_id')->where('used',0);
    }
    public function Distributed()
    {
        return $this->hasmany('App\Coordinate','coin_id');
    }
	public function Address()
    {
        return $this->hasone('App\Address','coin_id')->where('user_id',Auth::id())->withDefault([
        'address' => ''
    ]);;
    }
}
