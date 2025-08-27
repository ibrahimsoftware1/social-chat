<?php

namespace App\Models\chatting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'avatar',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot(['joined_at', 'is_admin', 'last_read_at', 'is_muted', 'notification_enabled'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class,'conversation_id')->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class,'conversation_id')->latest();
    }

    // Helper Methods
    public function isGroup()
    {
        return $this->type === 'group';
    }

    public function isPrivate()
    {
        return $this->type === 'private';
    }

    public function admins()
    {
        return $this->users()->wherePivot('is_admin', true);
    }

    public function addParticipants(array $userIds, $isAdmin = false)
    {
        $attachData = [];
        foreach ($userIds as $userId) {
            $attachData[$userId] = [
                'joined_at' => now(),
                'is_admin' => $isAdmin,
                'notification_enabled' => true,
            ];
        }

        return $this->users()->syncWithoutDetaching($attachData);
    }

    public function removeParticipants(array $userIds)
    {
        return $this->users()->detach($userIds);
    }

    public function makeAdmin($userId)
    {
        return $this->users()->updateExistingPivot($userId, [
            'is_admin' => true,
        ]);
    }

    public function removeAdmin($userId)
    {
        return $this->users()->updateExistingPivot($userId, [
            'is_admin' => false,
        ]);
    }

    public function markAsRead($userId)
    {
        return $this->users()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }

    public function mute($userId)
    {
        return $this->users()->updateExistingPivot($userId, [
            'is_muted' => true,
        ]);
    }

    public function unmute($userId)
    {
        return $this->users()->updateExistingPivot($userId, [
            'is_muted' => false,
        ]);
    }

    public function getUnreadCount($userId)
    {
        $lastRead = $this->users()
            ->where('user_id', $userId)
            ->first()
            ?->pivot
            ->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->when($lastRead, function ($query) use ($lastRead) {
                $query->where('created_at', '>', $lastRead);
            })
            ->count();
    }
}
