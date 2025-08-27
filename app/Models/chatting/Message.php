<?php

namespace App\Models\chatting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'edited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'edited_at' => 'datetime',
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statuses()
    {
        return $this->hasMany(MessageStatus::class, 'message_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }

    // Helpers
    public function markAsDelivered($userId)
    {
        $this->statuses()->updateOrCreate(
            ['user_id' => $userId],
            [
                'is_delivered' => true,
                'delivered_at' => now(),
            ]
        );
    }

    public function markAsRead($userId)
    {
        $this->statuses()->updateOrCreate(
            ['user_id' => $userId],
            [
                'is_delivered' => true,
                'is_read' => true,
                'delivered_at' => now(),
                'read_at' => now(),
            ]
        );
    }

    public function isReadBy($userId)
    {
        return $this->statuses()
            ->where('user_id', $userId)
            ->where('is_read', true)
            ->exists();
    }

    public function isDeliveredTo($userId)
    {
        return $this->statuses()
            ->where('user_id', $userId)
            ->where('is_delivered', true)
            ->exists();
    }

    public function edit($content)
    {
        $this->update([
            'content' => $content,
            'edited_at' => now(),
        ]);
    }

    public function isEdited()
    {
        return $this->edited_at !== null;
    }
}
