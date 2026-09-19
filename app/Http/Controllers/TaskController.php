<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskGroup;
use App\Models\User;
use App\Notifications\TaskCompleted;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request)
    {
        $task_group = TaskGroup::findOrFail($request->input('task_group_id'));
        $project = Project::findOrFail($task_group->project_id);

        $this->authorize('edit', $project);
        $this->authorize('create', [Task::class, $task_group]);

        $task = $this->createTask($request, $task_group);

        return $request->wantsJson()
            ? response()->json($task, 201)
            : redirect()->route('project', ['project' => $project]);
    }

    public function createTask(Request $request, TaskGroup $task_group)
    {

        $task = new Task;
        $data = $request->all();

        $task->name = $data['name'];
        $task->description = $data['description'] ?? '';
        $task->task_group_id = $task_group->id;
        $task->position = (Task::where('task_group_id', $task->task_group_id)->max('position') ?? 0) + 1;
        $task->creator_id = $request->user()->id;

        $task->save();

        try {
            foreach ($data['tags'] ?? [] as $tagId) {
                $tag = Tag::findOrFail($tagId);
                $task->attachTag($tag);
            }

            foreach ($data['assignees'] ?? [] as $assigneeId) {
                $assignee = User::findOrFail($assigneeId);
                $task->attachAssignee($assignee);
            }
        } catch (Exception $e) {
            $task->delete();

            throw $e;
        }

        return $task->fresh();
    }

    /**
     * Mark a task as completed. Used by the Web API.
     *
     * @param  Task  $task  the task to complete
     * @return JsonResponse the JSON response to the API
     */
    public function complete(Task $task)
    {

        $this->authorize('edit', $task->project);
        $this->authorize('completeTask', $task);

        $task->completed = true;
        $task->save();

        foreach ($task->assignees as $assignee) {
            $assignee->notify(new TaskCompleted($task));
        }

        return response()->json($task);
    }

    public function incomplete(Task $task)
    {

        $this->authorize('edit', $task->project);
        $this->authorize('incompleteTask', $task);

        $task->completed = false;
        $task->save();

        return response()->json($task);
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse|Response
     */
    public function show(Request $request, Project $project, Task $task)
    {

        $isApi = $request->expectsJson();

        $project = $isApi ? $task->project : $project;

        $this->authorize('view', [$task, $project]);

        $comments = $task->comments()->cursorPaginate(10);

        return $isApi
            ? response()->json((new TaskResource($task))->withComments($comments))
            : response()->view('pages.project.task', ['task' => $task, 'project' => $project, 'comments' => $comments]);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task)
    {

        $this->authorize('edit', $task->project);
        $this->authorize('edit', $task);

        $task = $this->editTask($task, $request);

        return $request->wantsJson()
            ? response()->json($task)
            : redirect()->route('project.task.info', ['project' => $project, 'task' => $task]);
    }

    public function editTask(Task $task, Request $request)
    {

        $data = $request->all();

        if (($data['task_group_id'] ??= null) !== null) {
            $task->task_group_id = $data['task_group_id'];
        }

        if (($data['position'] ??= null) !== null) {
            $task->position = $data['position'];
        }

        if (($data['description'] ??= null) !== null) {
            $task->description = $data['description'];
        }

        if (($data['name'] ??= null) !== null) {
            $task->name = $data['name'];
        }

        $task->tags()->detach();
        $task->assignees()->detach();
        foreach ($data['tags'] ?? [] as $tagId) {
            $tag = Tag::findOrFail($tagId);
            $task->attachTag($tag);
        }

        foreach ($data['assignees'] ?? [] as $assigneeId) {
            $assignee = User::findOrFail($assigneeId);
            $task->attachAssignee($assignee);
        }

        $task->push();

        return $task->fresh();
    }

    public function createComment(Request $request, Project $project, Task $task)
    {
        $data = $request->all();

        $this->authorize('edit', $task);

        $task_comment = new TaskComment;

        $task_comment->content = $data['content'];
        $task_comment->author_id = $request->user()->id;
        $task_comment->task_id = $task->id;
        $task_comment->save();

        return $request->wantsJson()
            ? response()->json([new TaskCommentResource($task_comment)], 201)
            : redirect()->route('project.task.info', ['project' => $project, 'task' => $task]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, Task $task)
    {

        $this->authorize('delete', $task);
        $task->delete();

        return response()->json($task);
    }
}
