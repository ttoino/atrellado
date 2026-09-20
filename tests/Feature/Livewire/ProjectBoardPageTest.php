<?php

use App\Livewire\ProjectBoardPage;
use App\Livewire\TaskOverlay;
use App\Models\Tag;
use App\Models\TaskComment;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = makeProject(makeUser());

    $this->get(route('project.board', $project))->assertRedirect(route('login'));
});

it('renders groups and tasks in position order', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    makeTask($group, 0, ['name' => 'First task']);
    makeTask($group, 1, ['name' => 'Second task']);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->assertSee($group->name)
        ->assertSeeInOrder(['First task', 'Second task']);
});

it('creates a group', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->set('newGroupName', 'Doing')
        ->call('createGroup')
        ->assertHasNoErrors()
        ->assertSee('Doing');

    expect($project->taskGroups()->sole('name')->name)->toBe('Doing');
});

it('renames and deletes a group', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->set("groupNames.{$group->id}", 'Renamed group')
        ->call('renameGroup', $group->id)
        ->assertHasNoErrors();

    expect($group->fresh()->name)->toBe('Renamed group');

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->call('deleteGroup', $group->id);

    expect($group->fresh())->toBeNull();
});

it('forbids deleting a group that still has tasks', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    makeTask($group, 0);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->call('deleteGroup', $group->id)
        ->assertForbidden();
});

it('quick-creates a task in a group', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->set("newTaskNames.{$group->id}", 'A quick task')
        ->call('createTask', $group->id)
        ->assertHasNoErrors()
        ->assertSee('A quick task');

    expect($group->tasks()->sole('name')->name)->toBe('A quick task');
});

it('creates a task with tags and assignees from the full form', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $tag = Tag::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->set('name', 'Full task')
        ->set('description', 'A longer description')
        ->set('taskGroupId', $group->id)
        ->set('tags', [$tag->id])
        ->call('createFullTask')
        ->assertHasNoErrors();

    $task = $group->tasks()->sole();
    expect($task->tags)->toHaveCount(1);
});

it('repositions tasks within and across groups', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $a = makeGroup($project, 0);
    $b = makeGroup($project, 1);
    $t1 = makeTask($a, 0);
    $t2 = makeTask($a, 1);
    $t3 = makeTask($b, 0);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->call('repositionTask', $t1->id, $a->id, 2);

    expect($a->fresh()->tasks->pluck('id')->all())->toBe([$t2->id, $t1->id]);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->call('repositionTask', $t3->id, $a->id, 1);

    expect($a->fresh()->tasks->pluck('id')->all())->toBe([$t2->id, $t3->id, $t1->id])
        ->and($b->fresh()->tasks)->toHaveCount(0);
});

it('repositions groups', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $a = makeGroup($project, 0);
    $b = makeGroup($project, 1);
    $c = makeGroup($project, 2);

    Livewire::actingAs($coordinator)
        ->test(ProjectBoardPage::class, ['project' => $project])
        ->call('repositionGroup', $c->id, 1);

    expect($project->taskGroups()->orderBy('position')->pluck('id')->all())
        ->toBe([$a->id, $c->id, $b->id]);
});

it('scopes tasks to their project', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $otherTask = makeTask(makeGroup(makeProject($coordinator), 0), 0);

    $this->actingAs($coordinator)
        ->get(route('project.task.info', ['project' => $project, 'task' => $otherTask]))
        ->assertNotFound();
});

it('shows the task overlay with its comments', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $task = makeTask(makeGroup($project, 0), 0, ['name' => 'Overlay task']);
    TaskComment::factory()->create([
        'task_id' => $task->id,
        'author_id' => $coordinator->id,
        'content' => 'Task comment!',
    ]);

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->assertSee('Overlay task')
        ->assertSee('Task comment!');
});

it('completes and incompletes a task', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $task = makeTask(makeGroup($project, 0), 0);

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->call('complete');

    expect($task->fresh()->completed)->toBeTruthy();

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->call('incomplete');

    expect($task->fresh()->completed)->toBeFalsy();
});

it('edits and deletes a task', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $task = makeTask(makeGroup($project, 0), 0);

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->call('edit')
        ->set('editName', 'Renamed task')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    expect($task->fresh()->name)->toBe('Renamed task');

    Livewire::actingAs($coordinator)
        ->test(TaskOverlay::class, ['task' => $task])
        ->call('deleteTask')
        ->assertRedirect(route('project.board', $project));

    expect($task->fresh())->toBeNull();
});

it('hides comment edit controls from other members', function () {
    $password = Hash::make('password123');
    $author = makeUser(['password' => $password]);
    $other = makeUser(['password' => $password]);
    $project = makeProject($author);
    $project->users()->attach($other->id);
    $task = makeTask(makeGroup($project, 0), 0);
    TaskComment::factory()->create([
        'task_id' => $task->id,
        'author_id' => $author->id,
        'content' => 'My comment',
    ]);

    Livewire::actingAs($author)
        ->test(TaskOverlay::class, ['task' => $task])
        ->assertSee('Edit');

    Livewire::actingAs($other)
        ->test(TaskOverlay::class, ['task' => $task])
        ->assertDontSee('editComment');
});
