<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'content' => $this->content,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'media' => PostMediaResource::collection($this->whenLoaded('media')),
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'shares_count' => $this->shares_count,
            'views_count' => $this->views_count,
            'is_liked' => $this->when(auth()->check(), fn() => $this->isLikedBy(auth()->id())),
            'is_pinned' => $this->is_pinned,
            'comments_enabled' => $this->comments_enabled,
            'hashtags' => $this->metadata['hashtags'] ?? [],
            'mentions' => $this->metadata['mentions'] ?? [],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
