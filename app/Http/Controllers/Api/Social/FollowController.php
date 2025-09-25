<?php

namespace App\Http\Controllers\Api\Social;

use App\Http\Controllers\Controller;
use App\Models\social\Follow;
use App\Models\User;
use App\Traits\ApiResponse;

class FollowController extends Controller
{
    use ApiResponse;

    public function follow(User $user){

        if($user->id == auth()->id()){
            return $this->error('You cannot follow yourself',null,400);
        }

        $existing=Follow::where('follower_id',auth()->id())
            ->where('following_id',$user->id)
            ->first();

        if($existing){
            return $this->error('You are already following this user',null,400);
        }
        Follow::create([
            'follower_id'=>auth()->id(),
            'following_id'=>$user->id,
            'accepted_at'=>$user->is_private? null :now()
        ]);
        // TODO: Send notification

        return $this->success(
            $user->is_private ? 'Follow request sent' : 'User followed successfully'
        );
    }
    public function unfollow(User $user)
    {
        $follow = Follow::where('follower_id', auth()->id())
            ->where('following_id', $user->id)
            ->first();

        if (!$follow) {
            return $this->error('Not following this user', 400);
        }

        $follow->delete();

        return $this->success('Unfollowed successfully');
    }

    public function followers($userId)
    {
        $followers = Follow::where('following_id', $userId)
            ->whereNotNull('accepted_at')
            ->with('follower')
            ->paginate(20);

        return $this->success('Followers retrieved', $followers);
    }

    public function following($userId)
    {
        $following = Follow::where('follower_id', $userId)
            ->whereNotNull('accepted_at')
            ->with('following')
            ->paginate(20);

        return $this->success('Following retrieved', $following);
    }
}
