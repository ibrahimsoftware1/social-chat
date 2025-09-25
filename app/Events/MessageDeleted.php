<?php

namespace App\Events;

use App\Models\chatting\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $messageId;
    public $conversationId;
    public function __construct(int $messageId , int $conversationId)
    {
        $this->messageId=$messageId;
        $this->conversationId=$conversationId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.' .$this->conversationId),
        ];
    }
    public function broadcastAs()
    {
        return 'message.deleted';
    }
    public function broadcastWith():array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'deleted_at' => now()->toISOString(),
        ];
    }
}
