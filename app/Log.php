<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
     protected $table = 'logs';

   public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}