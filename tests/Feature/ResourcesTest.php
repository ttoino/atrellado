<?php

use App\Models\TaskComment;
use Illuminate\Support\Facades\Hash;

it('marks a task comment as editable for its author but not for another member', function () {
    // Same hash for both users: AuthenticateSession otherwise logs out the
    // second actingAs user mid-test over a password_hash_web mismatch.
    $password = Hash::make('password123');
    $author = makeUser(['password' => $password]);
    $other = makeUser(['password' => $password]);
    $project = makeProject($author);
    $project->users()->attach($other->id);
    $group = makeGroup($project, 1);
    $task = makeTask($group, 1);

    $response = $this->actingAs($author)->postJson('/api/task-comment', [
        'task_id' => $task->id,
        'content' => 'Looks good to me!',
    ]);

    $response->assertCreated()->assertJsonPath('editable', true);

    $this->actingAs($other)
        ->getJson("/api/task-comment/{$response->json('id')}")
        ->assertOk()
        ->assertJsonPath('editable', false);
});

it('wraps comment listings in the resource pagination envelope', function () {
    $author = makeUser();
    $project = makeProject($author);
    $group = makeGroup($project, 1);
    $task = makeTask($group, 1);

    TaskComment::factory()->count(3)->create([
        'task_id' => $task->id,
        'author_id' => $author->id,
    ]);

    $this->actingAs($author)->getJson("/api/task-comment?task_id={$task->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.editable', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'content', 'author_id', 'task_id', 'author', 'editable'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ]);
});
