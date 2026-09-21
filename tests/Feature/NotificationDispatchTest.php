<?php

use App\Livewire\ProjectBoardPage;
use App\Livewire\TaskOverlay;
use App\Models\Notification;
use App\Models\TaskComment;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use Livewire\Livewire;

it('notifies task assignees with a denormalized payload when a task is commented', function () {
    $coordinator = makeUser();
    $assignee = makeUser();
    $project = makeProject($coordinator);
    $project->users()->save($assignee);
    $group = makeGroup($project, 0);
    $task = makeTask($group, 0);
    $task->attachAssignee($assignee);

    $comment = new TaskComment(['content' => 'Looks good']);
    $comment->task_id = $task->id;
    $comment->author_id = $coordinator->id;
    $comment->save();

    $notification = Notification::where('notifiable_id', $assignee->id)->sole();

    expect($notification->type)->toBe(TaskCommented::class)
        ->and($notification->data)->toMatchArray([
            'comment_id' => $comment->id,
            'task_id' => $task->id,
            'task_name' => $task->name,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'author_name' => $coordinator->name,
            'url' => route('project.task.info', ['project' => $project, 'task' => $task]),
        ])
        ->and($notification->getRawOriginal('data'))->not->toContain('model:');
});

it('notifies newly attached assignees when a task is created or updated', function () {
    $coordinator = makeUser();
    $first = makeUser();
    $second = makeUser();
    $project = makeProject($coordinator);
    $project->users()->save($first);
    $project->users()->save($second);
    $group = makeGroup($project, 0);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->set('name', 'Test task')
        ->set('taskGroupId', $group->id)
        ->set('assignees', [$first->id])
        ->call('createFullTask')
        ->assertHasNoErrors();

    $task = $group->tasks()->sole();

    expect(Notification::where('notifiable_id', $first->id)->where('type', TaskAssigned::class)->count())->toBe(1);

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->call('edit')
        ->set('editAssignees', [$first->id, $second->id])
        ->call('save')
        ->assertHasNoErrors();

    // The already-assigned user is not notified again.
    expect(Notification::where('notifiable_id', $first->id)->where('type', TaskAssigned::class)->count())->toBe(1)
        ->and(Notification::where('notifiable_id', $second->id)->where('type', TaskAssigned::class)->count())->toBe(1);

    $notification = Notification::where('notifiable_id', $second->id)->sole();

    expect($notification->data)->toMatchArray([
        'task_id' => $task->id,
        'task_name' => 'Test task',
        'project_id' => $project->id,
        'project_name' => $project->name,
        'url' => route('project.task.info', ['project' => $project, 'task' => $task->id]),
    ])
        ->and($notification->getRawOriginal('data'))->not->toContain('model:');
});
