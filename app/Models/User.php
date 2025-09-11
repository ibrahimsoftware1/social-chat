<?php

namespace App\Models;

use App\Models\chatting\Conversation;
use App\Models\chatting\Message;
use App\Models\chatting\MessageAttachment;
use App\Models\chatting\MessageStatus;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_seen_at',
        'is_online',
        'username',
        'bio',
        'profile_completed',
        'gender',
        'banned_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected static function boot()
    {
        parent::boot();

        // Override reset password notification
        static::retrieved(function ($user) {
            ResetPassword::createUrlUsing(function ($user, string $token) {
                // Here, you return the API/frontend reset link
                return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . $user->email;
            });
        });
    }

    public function messages():HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot(['joined_at','is_admin', 'last_read_at', 'is_muted', 'notification_enabled'])
            ->withTimestamps();
    }

    public function messageStatuses()
    {
        return $this->hasMany(MessageStatus::class, 'user_id');
    }
    public function messageAttachments()
    {
        return $this->hasMany(MessageAttachment::class, 'user_id');
    }

    public function isInConversation($conversationId): bool
    {
        return $this->conversations()->where('conversation_id', $conversationId)->exists();
    }

    public function markAsOnline()
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }
    public function markAsOffline()
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }
    public function unreadMessagesCount($conversationId = null)
    {
        $query = Message::whereHas('conversation.users', function ($q) {
            $q->where('user_id', $this->id);
        })->where('user_id', '!=', $this->id);

        if ($conversationId) {
            $query->where('conversation_id', $conversationId);
        }

        return $query->whereDoesntHave('statuses', function ($q) {
            $q->where('user_id', $this->id)->where('is_read', true);
        })->count();
    }
    public function isVerified():bool
    {
        return $this->hasVerifiedEmail();
    }



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen_at' => 'datetime',
        'is_online' => 'boolean',
        'banned_at' => 'datetime',
        ];

}
