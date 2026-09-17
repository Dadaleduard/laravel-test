<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachTagsRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Tag;

class PostTagController extends Controller
{
    public function store(AttachTagsRequest $request, Post $post)
    {
        $post->tags()->syncWithoutDetaching($request->validated()['tag_ids']);

        return new PostResource($post->load('user', 'tags'));
    }

    public function destroy(Post $post, Tag $tag)
    {
        $post->tags()->detach($tag->id);

        return response()->noContent();
    }
}
