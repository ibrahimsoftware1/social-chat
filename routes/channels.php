<?php

use App\Models\chatting\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {

    \Log::info('Channel auth attempt', [
        'user_id' => $user->id,
        'conversation_id' => $conversation->id,
        'is_participant' => $user->isInConversation($conversation->id)
    ]);

    if ($user->isInConversation($conversation->id)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    return false;
});

Broadcast::channel('online', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
    ];
});
