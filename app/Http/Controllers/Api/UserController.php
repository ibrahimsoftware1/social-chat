<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use ApiResponse;

    public function profile(Request $request){

        $user=$request->user()->load('roles','permissions');
        return $this->success('User profile',
        ['user'=>new UserResource($user),
         'status'=>[
             'conversations_count'=>$user->conversations->count(),
             'unread_messages_count'=>$user->unreadMessagesCount(),
             'member_since'=>$user->created_at->diffForHumans(),]
        ]);
    }

    public function updateProfile(Request $request){

        $request->validate([
            'name'=>'sometimes|string|max:255',
            'username'=>'sometimes|string|max:255',
            'bio'=>'sometimes|string|max:255',
        ]);
        $user=$request->user();
        $user->update($request->only('name','username','bio'));
        $user->update(['profile_completed'=>true]);

        return $this->success('Profile updated',new UserResource($user));

    }

    public function updateAvatar(Request $request){
        $request->validate([
            'avatar'=>'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user=$request->user();
        if($user->avatar && Storage::exists($user->avatar)){
            Storage::delete($user->avatar);
        }

        $path=$request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar'=>Storage::url($path)]);

        return $this->success('Avatar updated successfully',
            ['avatar_url'=>$user->avatar]);
    }

    public function removeAvatar(Request $request)
    {
        $user=$request->user();
        if($user->avatar && Storage::exists($user->avatar)){
            Storage::delete($user->avatar);
        }
        $user->update(['avatar'=>null]);
        return $this->success('Avatar removed successfully');

    }

    public function changePassword(Request $request){
        $request->validate([
            'current_password'=>'required|string',
            'new_password'=>'required|string|min:8|confirmed',
        ]);

        $user=$request->user();

        if(!Hash::check($request->current_password,$user->password)){
            return $this->error('Current password is incorrect',400);
        }

        $user->update(['password'=>Hash::make($request->new_password)]);

        return $this->success('Password changed successfully');
    }

    public function conversations(Request $request){
        $user=$request->user();
        $conversations=$user->conversations()
            ->with(['lastMessage','users'])
            ->latest('last_message_at')
            ->paginate(10);

    return $this->success('Conversations retrieved successfully ',$conversations);
    }


    public function deleteAccount(Request $request){
        $request->validate([
            'password'=>'required|string',
            'confirm'=>'required|in:DELETE',
        ]);
        $user=$request->user();

        if(!Hash::check($request->password,$user->password)){
            return $this->error('Password is incorrect',400);
        }

        // Delete user data
        $user->tokens()->delete();
        $user->conversations()->detach();
        $user->messages()->delete();

        if($user->avatar && Storage::exists($user->avatar)){

            Storage::delete($user->avatar);
        }

        $user->delete();
        return $this->success('Account deleted successfully');

    }



}
