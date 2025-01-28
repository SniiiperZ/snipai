<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageStreamed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channel,
        public string $content,
        public bool $isComplete = false,
        public bool $error = false
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageStreamed';
    }

    public function broadcastWith(): array
    {
        return [
            'content' => $this->content,
            'isComplete' => $this->isComplete,
            'error' => $this->error,
        ];
    }
}
