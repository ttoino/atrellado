<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(StoreTagRequest $request)
    {

        $project = Project::findOrFail($request->input('project_id'));

        $this->authorize('edit', $project);
        $this->authorize('create', [Tag::class, $project]);

        $tag = $this->createTag($project, $request);

        return $request->wantsJson()
            ? response()->json($tag->toArray(), 201)
            : redirect()->route('project.tag', ['project' => $project, 'tag' => $tag]);
    }

    public function createTag(Project $project, Request $request)
    {

        $tag = new Tag;
        $data = $request->only(['title', 'color']);

        $tag->title = $data['title'];
        $tag->color = intval(substr($data['color'], 1), 16);

        $project->tags()->save($tag);

        return $tag->fresh();
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(Request $request, Project $project, Tag $tag)
    {

        $this->authorize('view', $tag);

        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $this->authorize('edit', $tag->project);
        $this->authorize('update', $tag);

        $tag = $this->editTag($tag, $request);

        return response()->json($tag);
    }

    public function editTag(Tag $tag, Request $request)
    {

        $data = $request->only(['title', 'color']);

        if (($data['title'] ??= null) !== null) {
            $tag->title = $data['title'];
        }

        if (($data['color'] ??= null) !== null) {
            $tag->color = intval(substr($data['color'], 1), 16);
        }

        $tag->save();

        return $tag->fresh();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, Tag $tag)
    {

        $this->authorize('edit', $tag->project);
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->json($tag);
    }
}
