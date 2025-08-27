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
            'avatar'=>$this->avatar,
            'created_by'=>$this->created_by,
            'last_message_at'=>$this->last_message_at?->toISOString(),
            'created_at'=>$this->created_at->toISOString(),
            'updated_at'=>$this->updated_at->toISOString(),

            'users'=>UserResource::collection($this->whenLoaded('users')),
            'last_message'=>new MessageResource($this->whenLoaded('lastMessage')),
            'messages'=>MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
