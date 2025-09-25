<?php

namespace App\Models\social;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable=[
        'user_id',
        'content',
        'type',
        'visibility',
        'metadata',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'is_pinned',
        'comments_enabled',
        'deleted_at'
    ];


    protected $casts = [
        'metadata' => 'array',
        'is_pinned' => 'boolean',
        'comments_enabled' => 'boolean',
    ];

    public function user()
    {
     return $this->belongsTo(User::class,'user_id');
    }
    public function likes()
    {
        return $this->hasMany(Like::class,'post_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class,'post_id');
    }
    public function media()
    {
        return $this->hasMany(PostMedia::class,'post_id')->orderBy('order');
    }

    //Helpers
    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id',$userId)->exists();
    }

    public function incrementViewCount(){
        $this->increment('views_count');
    }

    public function extractHashtags(){
        preg_match_all('/#\w+/', $this->content, $hashtags);
        return $hashtags[0] ?? [];
    }
    public function extractMentions()
    {
        preg_match_all('/@(\w+)/', $this->content, $mentions);
        return $mentions[1] ?? [];
    }

}
