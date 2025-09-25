<?php

namespace App\Http\Controllers\Api\Chatting;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\UserStoppedTyping;
use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\chatting\Conversation;
use App\Models\chatting\Message;
use App\Traits\ApiResponse;
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

            // Fire the event directly - ShouldBroadcastNow will handle immediate broadcasting
            event(new MessageSent($message));

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

        broadcast(new MessageUpdated($message))->toOthers();


        return $this->success('Message updated successfully',
            new MessageResource($message));
    }

    //delete a message
    public function destroy(Message $message){

        //check if the user is the owner of the message or an admin
        if($message->user_id !==auth()->id() && !auth()->user()->hasRole('admin')){
            return $this->error('You Can Only Delete Your Own Messages',null,403);
        }

        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        $message->delete();
        broadcast(new MessageDeleted($messageId,$conversationId))->toOthers();

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

        // Fire the event directly for immediate broadcasting
        event(new UserTyping($conversation, auth()->user()));

        return $this->success('Typing started successfully');
    }

    public function stopTyping(Conversation $conversation)
    {
        if (!auth()->user()->isInConversation($conversation->id)) {
            return $this->error('You are not a participant of this conversation', 403);
        }

        // Fire the event directly for immediate broadcasting
        event(new UserStoppedTyping($conversation, auth()->user()));

        return $this->success('Stop typing indicator sent');
    }

}
