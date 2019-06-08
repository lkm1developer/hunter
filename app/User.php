<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','username','username',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
	    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($password)
    {
        if ( !empty($password) ) {
            $this->attributes['password'] = bcrypt($password);
        }
    }
    public function Hunted()
    {
        return $this->hasmany('App\Hunted');
    }
    public function lifetime()
    {
        return $this->hasmany('App\Hunted')->withTrashed();
    }
    public function Subscription()
    {
        return $this->hasone('App\Subscriber')->whereDate('till','>=',now())->where('active',true);
    }
    public function Usermeta()
    {
        return $this->hasOne('App\Usermeta');
    }

}
