<?php

namespace App\Events;

use App\Models\chatting\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public $user;

    public function __construct(Message $message, User $user)
    {
        $this->message=$message;
        $this->user=$user;

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
        return 'message.read';
    }

    public function broadcastWith():array
    {
        return[
            'message_id'=>$this->message->id,
            'user_id'=>$this->user->id,
            'user_name'=>$this->user->name,
            'read_at'=>now()->toISOString(),
        ];
    }
}
