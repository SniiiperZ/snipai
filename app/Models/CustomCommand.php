<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomCommand extends Model
{
    protected $fillable = ['command', 'description', 'action', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
