<?php

namespace App\Http\Controllers\Api\Social;

use App\Http\Controllers\Controller;
use App\Models\social\Like;
use App\Models\social\Post;
use App\Traits\ApiResponse;

class LikeController extends Controller
{
    use ApiResponse;

    public function likePost(Post $post){
        $like=Like::where('user_id',auth()->id())
            ->where('post_id',$post->id)
            ->first();

        //unlike

        if($like){
            $like->deleted();
            $post->decrement('likes_count');
            $liked=false;
        }
        else{
            Like::create([
                'user_id'=>auth()->id(),
                'post_id'=>$post->id
            ]);
            $post->increment('likes_count');
            $liked=true;
        }

        return $this->success($liked? 'Post Liked' : 'Post UnLiked',[
            'liked'=>$liked,
            'likes_count'=>$post->fresh()->likes_count
        ]);
    }

    public function likers(Post $post){
        $likers=$post->likes()->with('user')->latest()->paginate(10);
        return $this->success('Likes Retrieved');
    }
}
