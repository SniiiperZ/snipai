<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-chat.{conversation}', function ($user, Conversation $conversation) {
    return (int) $conversation->user_id === (int) $user->id;
});
