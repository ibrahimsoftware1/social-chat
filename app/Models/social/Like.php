<?php

namespace App\Models\social;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'user_id',
        'post_id'
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function post(){
        return $this->belongsTo(Post::class,'post_id');
    }
}
