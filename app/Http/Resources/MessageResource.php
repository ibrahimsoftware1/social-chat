<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'conversation_id'=>$this->conversation_id,
            'user_id'=>$this->user_id,
            'content'=>$this->content,
            'type'=>$this->type,
            'metadata' => $this->metadata,
            'edited_at' => $this->edited_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),


            'user' => new UserResource($this->whenLoaded('user')),
            'attachments' => MessageAttachmentResource::collection($this->whenLoaded('attachments')),
            'statuses' => MessageStatusResource::collection($this->whenLoaded('statuses')),

            'is_edited'=>$this->isEdited(),
            'is_mine' => $this->user_id === auth()->id(),
            'is_read' => $this->when(auth()->check(), fn() =>
            $this->isReadBy(auth()->id())
            ),
            'is_delivered' => $this->when(auth()->check(), fn() =>
            $this->isDeliveredTo(auth()->id())
            ),




            ];
    }
}
