<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskGroupRequest;
use App\Http\Requests\UpdateTaskGroupRequest;
use App\Models\Project;
use App\Models\TaskGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskGroupController extends Controller
{
    public function show(Request $request, TaskGroup $taskGroup)
    {

        $this->authorize('view', $taskGroup);

        return response()->json($taskGroup);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function store(StoreTaskGroupRequest $request)
    {
        $project = Project::findOrFail($request->input('project_id'));

        $this->authorize('edit', $project);
        $this->authorize('create', [TaskGroup::class, $project]);

        $taskGroup = $this->createTaskGroup($request, $project);

        return $request->wantsJson()
            ? response()->json($taskGroup, 201)
            : redirect()->route('project', ['project' => $project]);
    }

    public function createTaskGroup(Request $request, Project $project)
    {

        $taskGroup = new TaskGroup;
        $data = $request->only(['name']);

        $taskGroup->name = $data['name'];
        $taskGroup->project_id = $project->id;
        $taskGroup->position = (TaskGroup::where('project_id', $taskGroup->project_id)->max('position') ?? 0) + 1;
        $taskGroup->save();

        return $taskGroup->fresh();
    }

    public function update(UpdateTaskGroupRequest $request, TaskGroup $taskGroup)
    {

        $this->authorize('edit', $taskGroup->project);
        $this->authorize('update', $taskGroup);

        $taskGroup = $this->updateTaskGroup($taskGroup, $request);

        return response()->json($taskGroup);
    }

    public function updateTaskGroup(TaskGroup $taskGroup, Request $request)
    {

        $data = $request->all();

        if (($data['position'] ??= null) !== null) {
            $taskGroup->position = $data['position'];
        }

        if (($data['name'] ??= null) !== null) {
            $taskGroup->name = $data['name'];
        }

        if (($data['description'] ??= null) !== null) {
            $taskGroup->description = $data['description'];
        }

        $taskGroup->save();

        return $taskGroup;
    }

    public function destroy(Request $request, TaskGroup $taskGroup)
    {

        $this->authorize('delete', $taskGroup);
        $taskGroup->delete();

        return response()->json($taskGroup);
    }
}
