<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Mytool extends Model
{
    protected $hidden = ["created_at", "updated_at"];
    public function Tool()
    {
        return $this->belongsTo('App\Tool','tool_id');
    }
}
