<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ThreadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
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
     * @return Response
     */
    public function store(Request $request)
    {
        $this->threadCreationValidator($request)->validate();

        $project = Project::findOrFail($request->input('project_id'));

        $this->authorize('edit', $project);
        $this->authorize('create', [Thread::class, $project]);

        $thread = $this->createThread($request, $project);

        return $request->wantsJson()
            ? response()->json($thread->toArray(), 201)
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

    public function threadCreationValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'title' => 'required|string|min:6|max:50',
            'content' => 'required|string|min:6|max:512',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Request $request, Project $project, Thread $thread)
    {

        $this->authorize('view', $thread);

        $thread->comments = $thread->comments()->cursorPaginate(10);

        return $request->wantsJson()
            ? response()->json($thread)
            : response()->view('pages.project.thread', ['project' => $project, 'thread' => $thread]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Thread $thread)
    {

        $this->threadEditionValidator($request)->validate();

        $this->authorize('edit', $thread->project);
        $this->authorize('update', $thread);

        $thread = $this->editThread($thread, $request);

        return response()->json($thread);
    }

    public function threadEditionValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'title' => 'string|min:6|max:50',
            'content' => 'string|min:6|max:512',
        ]);
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
     * @return Response
     */
    public function destroy(Request $request, Thread $thread)
    {

        $this->authorize('edit', $thread->project);
        $this->authorize('delete', $thread);

        $thread->delete();

        return response()->json($thread);
    }
}
