<?php

namespace App\Http\Controllers\Api\Chatting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\AddParticipantsRequest;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Requests\Conversation\UpdateConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\chatting\Conversation;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    use ApiResponse;

    //Users Conversations

    public function index(Request $request){
        $conversations=$request->user()
            ->conversations()
            ->with('Users','lastMessage.user')
            ->withCount('messages')
            ->orderBy('last_message_at','desc')
            ->paginate(10);

        return $this->success('Conversations retrieved successfully',
            ConversationResource::collection($conversations)->response()->getData(true));
    }


    //create new conversation
    public function store(StoreConversationRequest $request){
        DB::beginTransaction();
        try {
            if($request->type==='private' && count($request->user_ids)===1){
                $existConversation=$this->findExistingPrivateConversation(
                    auth()->id(),
                    $request->user_ids[0]
                );

                if($existConversation){
                    DB::commit();
                    return $this->success('Conversation Already Exists',
                    new ConversationResource($existConversation->load('Users')));
                }
            }

            //create conversation
            $conversation=Conversation::create([
                'type'=>$request->type,
                'name'=>$request->name,
                'description'=>$request->description,
                'created_by'=>auth()->id()
            ]);

            //add creator as admin
            $participants=[
                auth()->id()=>[
                    'joined_at'=>now(),
                    'is_admin'=>true,
                    'notification_enabled'=>true,]
            ];
            //add other users
            foreach($request->user_ids as $userId){
                if($userId!=auth()->id()){
                    $participants[$userId]=[
                        'joined_at'=>now(),
                        'is_admin'=>false,
                        'notification_enabled'=>true,
                    ];
                }
            }
            $conversation->users()->attach($participants);
            DB::commit();
            return $this->success('Conversation created successfully',
            new ConversationResource($conversation->load('Users')),
                201);
        }
        catch (\Exception $e){
            DB::rollBack();
            return $this->error('Failed to create conversation',null,500);
        }
    }

    //Conversation Details
    public function show(Conversation $conversation)
    {
            if (!auth()->user()->isInConversation($conversation->id)) {
                return $this->error('You are not a participant of this conversation', null, 403);
            }
            $conversation->load(['users', 'messages' => function ($query) {
                $query->latest()->limit(50);
            }]);
            $conversation->markAsRead(auth()->id());
            return $this->success('Conversation details retrieved successfully',
                new ConversationResource($conversation)
            );

    }

    //update Conversation For Group type

    public function update(UpdateConversationRequest $request,Conversation $conversation){

        if(!$conversation->isGroup()){
            return $this->error('Cannot Update Private Conversation',null,403);
        }

        //if user is admin
        $isAdmin=$conversation->users()
            ->wherePivot('user_id',auth()->id())
            ->wherePivot('is_admin',true)
            ->exists();

        if(!$isAdmin){
            return $this->error('Only Admin Can Update This Conversation',null,403);
        }

        $conversation->update($request->validated());

        return $this->success('Conversation updated successfully',
        new ConversationResource($conversation->load('Users')));
    }

    public function destroy(Conversation $conversation){

        //checks if user is in this conversation
        if(!auth()->user()->isInConversation($conversation->id)){
            return $this->error('You Are Not Participant of this Conversation',null,403);
        }
        if($conversation->isPrivate() || $conversation->users()->count()===1){
            $conversation->messages()->delete();
            $conversation->users()->detach();
            $conversation->delete();

            return $this->success('Conversation deleted successfully');
        }

        $conversation->removeParticipants([auth()->id()]);
        return $this->success('You left Conversation successfully');
    }

    //Add members to the group

    public function addParticipants(AddParticipantsRequest $request,Conversation $conversation){

        if(!$conversation->isGroup()){
            return $this->error('You cannot Add Participants to Private Conversation');
        }

        $isAdmin=$conversation->users()
            ->wherePivot('user_id',auth()->id())
            ->wherePivot('is_admin',true)
            ->exists();

        if(!$isAdmin){
            return $this->error('Only Admin Can Add Participants to Private Conversation');
        }

        $conversation->addParticipants($request->user_ids);
        return $this->success('Participant added successfully',
        new ConversationResource($conversation->load('Users'))
        );

    }

    //Remove members from group
    public function removeParticipant(Conversation $conversation,User $user){

        if(!$conversation->isGroup()){
            return $this->error('You cannot Remove Participants from private Conversation');
        }

        $isAdmin=$conversation->users()
            ->wherePivot('user_id',auth()->id())
            ->wherePivot('is_admin',true)
            ->exists();
        if(!$isAdmin && $user->id !== auth()->id()){
            return $this->error('Only Admins can remove Participants');
        }
        $conversation->removeParticipants([$user->id]);
        return $this->success('Participant removed successfully');
    }

    public function markAsRead(Conversation $conversation){

        if(!auth()->user()->isInConversation($conversation->id)){
            return $this->error('You are not a participant of this conversation', 403);
        }

        $conversation->markAsRead(auth()->id());
        return $this->success('Conversation marked as read successfully', 200);
    }

    //Get conversation messages
    public function messages(Conversation $conversation){

        if(!auth()->user()->isInConversation($conversation->id)){
            return $this->error('You are not a participant of this conversation', 403);
        }

        $messages=$conversation->messages()
            ->with(['user','attachments'])
            ->latest()
            ->paginate(50);

        return $this->success('Messages retrieved successfully',
        MessageResource::collection($messages)->response()->getData(true));
    }

    //Find Private Conversation if exists
    private function findExistingPrivateConversation($userId1, $userId2)
    {
        return Conversation::where('type', 'private')
            ->whereHas('users', function ($query) use ($userId1) {
                $query->where('user_id', $userId1);
            })
            ->whereHas('users', function ($query) use ($userId2) {
                $query->where('user_id', $userId2);
            })
            ->first();
    }




}
