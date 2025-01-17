<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantBehavior extends Model
{
    protected $fillable = ['behavior', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
