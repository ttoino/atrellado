<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadRequest;
use App\Http\Requests\UpdateThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\Project;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request, Project $project)
    {
        $this->authorize('viewCreationForm', [Thread::class, $project]);

        return response()->view('pages.project.forum.new', ['project' => $project]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(StoreThreadRequest $request)
    {
        $project = Project::findOrFail($request->input('project_id'));

        $this->authorize('edit', $project);
        $this->authorize('create', [Thread::class, $project]);

        $thread = $this->createThread($request, $project);

        return $request->wantsJson()
            ? response()->json(new ThreadResource($thread), 201)
            : redirect()->route('project.thread', ['project' => $project, 'thread' => $thread]);
    }

    public function createThread(Request $request, Project $project)
    {

        $thread = new Thread;
        $data = $request->only(['title', 'content']);

        $thread->title = $data['title'];
        $thread->content = $data['content'];
        $thread->author_id = $request->user()->id;

        $project->threads()->save($thread);

        return $thread->fresh();
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse|Response
     */
    public function show(Request $request, Project $project, Thread $thread)
    {

        $this->authorize('view', $thread);

        $comments = $thread->comments()->cursorPaginate(10);

        return $request->wantsJson()
            ? response()->json((new ThreadResource($thread))->withComments($comments))
            : response()->view('pages.project.thread', ['project' => $project, 'thread' => $thread, 'comments' => $comments]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function update(UpdateThreadRequest $request, Thread $thread)
    {

        $this->authorize('edit', $thread->project);
        $this->authorize('update', $thread);

        $thread = $this->editThread($thread, $request);

        return response()->json(new ThreadResource($thread));
    }

    public function editThread(Thread $thread, Request $request)
    {

        $data = $request->only(['title', 'content']);

        if (($data['title'] ??= null) !== null) {
            $thread->title = $data['title'];
        }

        if (($data['content'] ??= null) !== null) {
            $thread->content = $data['content'];
        }

        $thread->save();

        return $thread->fresh();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, Thread $thread)
    {

        $this->authorize('edit', $thread->project);
        $this->authorize('delete', $thread);

        $thread->delete();

        return response()->json(new ThreadResource($thread));
    }
}
