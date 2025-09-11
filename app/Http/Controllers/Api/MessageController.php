<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\chatting\Conversation;
use App\Models\chatting\Message;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    use ApiResponse;

    //send a message
    public function store(StoreMessageRequest $request , Conversation $conversation){

        if(!auth()->user()->isInConversation($conversation->id)){
            return $this->error('You are not a participant in this conversation',403);
        }
        DB::beginTransaction();
        try {
            $message=$conversation->messages()->create([
                'user_id'=>auth()->id(),
                'content'=>$request->input('content'),
                'type'=>$request->type ?? 'text',
                'metadata'=>$request->metadata,
            ]);
            // TODO: Broadcast new message event (WebSocket)
            DB::commit();

            return $this->success('Message sent successfully',
                new MessageResource($message->load('user')),201);

        }catch (\Exception $e){
            DB::rollBack();
            return $this->error('Failed to send message',null,500);
        }
    }

    //update a message
    public function update(StoreMessageRequest $request , Message $message){

        //check if the user is the owner of the message
        if($message->user_id !== auth()->id()){
            return $this->error('You Can Only Edit Your Own message',null,403);
        }

        //check if the message is older than 24 hours
        if($message->created_at->diffInHours(now())>24){
            return $this->error('You Can Only Edit Messages Within 24 Hours',null,403);
        }
        $message->update([
            'content'=>$request->input('content'),
            'edited_at'=>now(),
        ]);
        // TODO: Broadcast message updated event


        return $this->success('Message updated successfully',
            new MessageResource($message));
    }

    //delete a message
    public function destroy(Message $message){

        //check if the user is the owner of the message or an admin
        if($message->user_id !==auth()->id() && !auth()->user()->hasRole('admin')){
            return $this->error('You Can Only Delete Your Own Messages',null,403);
        }
        $message->delete();
        // TODO: Broadcast message deleted event

        return $this->success('Message deleted successfully');
    }

    //mark a message as read
    public function markAsRead(Message $message){

        if(!auth()->user()->isInConversation($message->conversation_id)){
            return $this->error('You are not a participant in this conversation',null,403);
        }
        $message->markAsRead(auth()->id());
        return $this->success('Message marked as read successfully');
    }

    public function typing(Conversation $conversation){

        if(!auth()->user()->isInConversation($conversation->id)){
            return $this->error('You are not a participant in this conversation',null,403);
        }

        // TODO: Broadcast typing event

        return $this->success('Typing started successfully');
    }

}
