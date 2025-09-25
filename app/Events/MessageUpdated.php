<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\chatting\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;

    public function __construct(Message $message)
    {
        $this->message=$message->load(['user', 'attachments']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.'. $this->message->conversation_id),
        ];
    }
    public function broadcastAs()
    {
        return 'message.updated';
    }
    public function broadcastWith():array
    {
        return[
            'message'=>new MessageResource($this->message),
        ];
    }
}
