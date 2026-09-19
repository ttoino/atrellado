<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadCommentRequest;
use App\Http\Requests\UpdateThreadCommentRequest;
use App\Http\Resources\ThreadCommentResource;
use App\Models\Thread;
use App\Models\ThreadComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ThreadCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {

        $threadId = $request->query('thread_id');

        $thread = Thread::findOrFail((int) $threadId);

        $this->authorize('viewAny', [ThreadComment::class, $thread]);

        $comments = ThreadComment::cursorPaginate(10);

        return ThreadCommentResource::collection($comments);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function store(StoreThreadCommentRequest $request)
    {
        $thread = Thread::findOrFail((int) $request->input('thread_id'));

        $this->authorize('edit', $thread->project);
        $this->authorize('create', [ThreadComment::class, $thread]);

        $threadComment = $this->createThreadComment($request, $thread);

        return response()->json(new ThreadCommentResource($threadComment));
    }

    public function createThreadComment(Request $request, Thread $thread)
    {

        $threadComment = new ThreadComment;

        $data = $request->only(['content']);

        $threadComment->content = $data['content'];
        $threadComment->author_id = $request->user()->id;
        $threadComment->thread_id = $thread->id;

        $threadComment->save();

        return $threadComment->fresh();
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(Request $request, ThreadComment $threadComment)
    {
        $this->authorize('view', [$threadComment]);

        return response()->json(new ThreadCommentResource($threadComment));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function update(UpdateThreadCommentRequest $request, ThreadComment $threadComment)
    {

        $thread = $threadComment->thread;

        $this->authorize('edit', $thread->project);
        $this->authorize('update', $threadComment);

        $threadComment = $this->updateThreadComment($threadComment, $request);

        return response()->json(new ThreadCommentResource($threadComment));

    }

    public function updateThreadComment(ThreadComment $threadComment, Request $request)
    {

        $data = $request->only('content');

        if (($data['content'] ?? null) !== null) {
            $threadComment->content = $data['content'];
        }

        $threadComment->save();

        return $threadComment->fresh();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy(ThreadComment $threadComment)
    {

        $this->authorize('edit', $threadComment->thread->project);
        $this->authorize('delete', $threadComment);

        $threadComment->delete();

        return response()->json(new ThreadCommentResource($threadComment));
    }
}
