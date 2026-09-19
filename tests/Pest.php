<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Small factories for the graph every feature test needs: a user, a project
// (its observer auto-enrolls the coordinator as member), a task group and a
// task, all with explicit sequential positions.

function makeUser(array $attributes = []): User
{
    return User::factory()->verified()->create($attributes);
}

function makeProject(User $coordinator, array $attributes = []): Project
{
    return Project::factory()->create($attributes + ['coordinator_id' => $coordinator->id]);
}

function makeGroup(Project $project, int $position, array $attributes = []): TaskGroup
{
    return TaskGroup::factory()->create($attributes + [
        'project_id' => $project->id,
        'position' => $position,
    ]);
}

function makeTask(TaskGroup $group, int $position, array $attributes = []): Task
{
    return Task::factory()->create($attributes + [
        'task_group_id' => $group->id,
        'position' => $position,
    ]);
}

// Positions keyed by model id, in ascending order, for compact assertions.
function taskPositions(TaskGroup $group): array
{
    return $group->tasks()->orderBy('position')->pluck('position', 'id')->all();
}

function groupPositions(Project $project): array
{
    return $project->taskGroups()->orderBy('position')->pluck('position', 'id')->all();
}
