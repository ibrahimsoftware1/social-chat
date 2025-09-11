<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\chatting\Conversation;
use App\Models\chatting\Message;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use ApiResponse;
    public function dashboard()
    {
        $stats=[
            'Total Users'=>User::count(),
            'Verified Users'=>User::whereNotNull('email_verified_at')->count(),
            'Online Users'=>User::where('is_online',true)->count(),
            'Total Conversations'=>Conversation::count(),
            'Total Mesages'=>Message::count(),
            'Today\'s Messages'=>Message::whereDate('created_at',today())->count(),

            //Users Growth
            'New Users Today'=>User::whereDate('created_at',today())->count(),
            'New Users This Week'=>User::whereBetween('created_at',[now()->startOfWeek(),now()])->count(),
            'New Users This Month'=>User::whereMonth('created_at',now()->month)->count(),

            'Roles'=>DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.name')
                ->get()
        ];

        $recent_users=User::latest()
            ->take(10)
            ->get();

        return $this->success('Dashboard Data Retrieved Successfully',
        [
            'Stats'=>$stats,
            'Recent Users'=>UserResource::collection($recent_users),
        ]);
    }

    //List Users With Filters
    public function users(Request $request){
        $query=User::query();

        //search
        if($request->has('search')){
            $search=$request->search;
            $query->where(function($q) use ($search){
                $q->where('name','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%");

            });
        }
        //filter by role
        if($request->has('role')){
            $query->role($request->role);
        }
        //filter by verified
        if($request->has('verified')){
            $query->where(function ($q) use ($request){
                $request->verified==='true'?
                    $q->whereNotNull('email_verified_at')
                    :$q->whereNull('email_verified_at');
            });
        }
        //filter by online
        if($request->has('is_online')){
            $query->where('is_online',$request->boolean('is_online'));
        }
        $users=$query->latest()->paginate(10);
        return $this->success('Users retrieved successfully',
        $users);
    }

    public function userDetails($id){
        $user=User::with(['roles','permissions','conversations'])
        ->findOrFail($id);

        $stats=[
            'Conversations Count'=>$user->conversations()->count(),
            'Messages Count'=>$user->messages()->count(),
            'Last Active'=>$user->last_seen_at?->diffForHumans()??'Never',
            'Member Since'=>$user->created_at->diffForHumans(),
        ];
        return $this->success('User details retrieved successfully',
        [
            'User'=>new UserResource($user),
            'Stats'=>$stats,
        ]);
    }

    public function ban($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return $this->error('You cannot ban Admin users', null, 403);
        }

        if ($user->banned_at) {
            return $this->error('User is already banned');
        }

        $user->update(['banned_at' => now()]);
        $user->tokens()->delete();

        return $this->success('User banned successfully', new UserResource($user->refresh()));
    }

    public function unban($id)
    {
        $user = User::findOrFail($id);

        if (! $user->banned_at) {
            return $this->error('User is not banned');
        }

        $user->update(['banned_at' => null]);

        return $this->success('User unbanned successfully', new UserResource($user->refresh()));
    }

    public function assignRole(Request $request,$id){
        $request->validate([
            'role'=>'required|exists:roles,name'
        ]);
        $user=User::findOrFail($id);

        $user->syncRoles($request->role);

        return $this->success('Role assigned successfully',new UserResource($user->load('roles')));
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting admins
        if ($user->hasRole('admin')) {
            return $this->error('Cannot delete admin users', 403);
        }

        // Delete user data
        $user->tokens()->delete();
        $user->conversations()->detach();
        $user->messages()->delete();
        $user->delete();

        return $this->success('User deleted successfully');
    }
}
