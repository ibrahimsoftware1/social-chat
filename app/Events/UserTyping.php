<?php

namespace App\Events;

use App\Models\chatting\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $user;
    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation , User $user)
    {
        $this->conversation=$conversation;
        $this->user=$user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.' . $this->conversation->id)];

    }

    public function broadcastAs()
    {
        return 'user.typing';
    }

    public function broadcastWith():array
    {
        return [
            'user_id'=>$this->user->id,
            'user_name'=>$this->user->name,
            'conversation_id'=>$this->conversation->id
        ];
    }
}
