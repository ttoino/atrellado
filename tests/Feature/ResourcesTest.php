<?php

use App\Models\TaskComment;
use App\Models\Thread;
use App\Models\ThreadComment;
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

it('embeds comments under comments.data in the thread json', function () {
    $author = makeUser();
    $project = makeProject($author);
    $thread = Thread::factory()->create([
        'project_id' => $project->id,
        'author_id' => $author->id,
    ]);

    ThreadComment::factory()->count(3)->create([
        'thread_id' => $thread->id,
        'author_id' => $author->id,
    ]);

    $this->actingAs($author)->getJson("/api/thread/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('editable', true)
        ->assertJsonCount(3, 'comments.data')
        ->assertJsonPath('comments.data.0.editable', true)
        ->assertJsonStructure([
            'id',
            'title',
            'content',
            'editable',
            'comments' => [
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            ],
        ]);
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
