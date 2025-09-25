<?php

namespace App\Http\Controllers\Api\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\social\Follow;
use App\Models\social\Post;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    use ApiResponse;


    public function index(Request $request)
    {

        $posts = Post::with(['user', 'media'])
            ->where('visibility', 'public')
            ->orWhere('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return $this->success('Posts retrieved successfully',
            PostResource::collection($posts));
    }

    public function userPosts($userId)
    {
        $Posts = Post::with(['user', 'media'])
            ->where('user_id', $userId)
            ->when($userId !== auth()->id(), function ($query) {
                $query->where('visibility', 'public');
            })
            ->latest()
            ->paginate(10);


        return $this->success('Posts retrieved successfully',
            PostResource::collection($Posts));
    }

    public function store(StorePostRequest $request)
    {

        DB::beginTransaction();
        try {
            $post = Post::create([
                'user_id' => auth()->id(),
                'content' => $request->input('content'),
                'visibility' => $request->visibility ?? 'public',
                'type' => $this->determinePostType($request),
                'metadata' => [
                    'hashtags' => $this->extractHashtags($request->input('content')),
                    'mentions' => $this->extractMentions($request->input('content')),
                ]
            ]);

            // Handle media uploads
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $index => $file) {
                    $path = $file->store('posts', 'public');

                    $post->media()->create([
                        'media_url' => Storage::url($path),
                        'media_type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                        'order' => $index,
                        'metadata' => [
                            'original_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ],
                    ]);
                }
            }
            DB::commit();
            return $this->success('Post created successfully',
                new PostResource($post->load('user', 'media')), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create post: '.$e->getMessage(), null, 500);
        }
    }

    public function show(Post $post)
    {

        if ($post->visibility !== 'public' && 'user_id' !== auth()->id()) {
            return $this->error('You are not authorized to view this post', null, 403);
        }
        $post->incrementViewCount();

        return $this->success('Post retrieved successfully',
            new PostResource($post->load(['user', 'media', 'comments.user'])
            ));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {

        if ($post->user_id !== auth()->id()) {
            return $this->error('You can only update your own posts', null, 403);
        }
        $post->update($request->validated());

        return $this->success('Post updated successfully',
        new PostResource($post->load(['user', 'media'])));

    }

    public function destroy(Post $post){

        if($post->user_id !== auth()->id() && !auth()->user()->hasRole('admin')){
            return $this->error('You can only delete your own posts',null,403);
        }

        foreach ($post->media as $media){
            Storage::delete(str_replace('/storage/', 'public/', $media->media_url));
        }
        $post->delete();
        return $this->success('Post deleted successfully');
    }

    public function togglePin(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $post->update(['is_pinned' => !$post->is_pinned]);

        return $this->success(
            $post->is_pinned ? 'Post pinned' : 'Post unpinned',
            new PostResource($post)
        );
    }

    // Helper methods
    private function determinePostType($request)
    {
        if (!$request->hasFile('media')) {
            return 'text';
        }

        $hasVideo = false;
        $hasImage = false;

        foreach ($request->file('media') as $file) {
            if (str_starts_with($file->getMimeType(), 'video/')) {
                $hasVideo = true;
            } else {
                $hasImage = true;
            }
        }

        if ($hasVideo && !$hasImage) return 'video';
        if ($hasImage && !$hasVideo) return 'image';
        return 'mixed';
    }

    private function extractHashtags($content)
    {
        if (!$content) return [];
        preg_match_all('/#\w+/', $content, $hashtags);
        return $hashtags[0] ?? [];
    }

    private function extractMentions($content)
    {
        if (!$content) return [];
        preg_match_all('/@(\w+)/', $content, $mentions);
        return $mentions[1] ?? [];
    }

    public function feed(Request $request)
    {
        $followingIds = Follow::where('follower_id', auth()->id())
            ->whereNotNull('accepted_at')
            ->pluck('following_id');

        $followingIds->push(auth()->id());

        $posts = Post::with(['user', 'media'])
            ->whereIn('user_id', $followingIds)
            ->where('visibility', '!=', 'private')
            ->latest()
            ->paginate(10);

        return $this->success('Feed retrieved successfully',
            PostResource::collection($posts));
    }
}
