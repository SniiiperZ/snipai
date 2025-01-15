<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInstruction extends Model
{
    protected $fillable = ['content', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
