<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'=>$this->id,
            'type'=>$this->type,
            'name'=>$this->name,
            'description'=>$this->description,
            'Number of Users'=>$this->users->count(),
            'avatar'=>$this->avatar,
            'created_by'=>$this->created_by,
            'last_message_at'=>$this->last_message_at?->toISOString(),


            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                       'username' => $user->username,
                            ];
                   });
                }),
            'last_message'=>new MessageResource($this->whenLoaded('lastMessage')),
            'messages'=>MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
