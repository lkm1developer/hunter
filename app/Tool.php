<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Auth;
class Tool extends Model
{
    protected $hidden = ["created_at", "updated_at"];

    public function My_Purchased()
    {
        return $this->hasOne('App\Mytool','tool_id')->where('active',1)->where('user_id',Auth::id());
    }
}
