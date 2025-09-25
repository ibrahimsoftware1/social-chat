<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $user;
    public $isOnline;

    public function __construct(User $user, bool $isOnline)
    {
        $this->user = $user;
        $this->isOnline = $isOnline;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
       $channels = [];
       $channels[]=new Channel('online-status');

       foreach($this->user->conversations as $conversation) {
           new PresenceChannel('conversation.' .$conversation->id);
       }
       return $channels;
    }
    public function broadcastAs(){
        return 'online-status-changed';
    }
    public function broadcastWith():array{
        return[
            'user_id'=>$this->user->id,
            'user_name'=>$this->user->name,
            'is_online'=>$this->isOnline,
            'last_seen_at'=>$this->user->last_seen_at?->toDateTimeString(),
        ];
    }
}
