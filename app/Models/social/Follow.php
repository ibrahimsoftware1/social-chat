<?php

namespace App\Models\social;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = [
        'follower_id',
        'followed_id',
        'accepted_at',
        'created_at',
        'updated_at'
    ];
    protected $casts = [
        'accepted_at' => 'datetime'
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
    public function isAccepted()
    {
        return $this->accepted_at !== null;
    }

}
