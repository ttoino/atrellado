<?php

use App\Livewire\ProjectForumPage;
use App\Livewire\ThreadOverlay;
use App\Models\Project;
use App\Models\Thread;
use App\Models\ThreadComment;
use App\Models\User;
use Livewire\Livewire;

function makeThread(Project $project, User $author, array $attributes = []): Thread
{
    return Thread::factory()->create($attributes + [
        'project_id' => $project->id,
        'author_id' => $author->id,
    ]);
}

it('requires authentication', function () {
    $project = makeProject(makeUser());

    $this->get(route('project.forum', $project))->assertRedirect(route('login'));
});

it('lists the project threads', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    makeThread($project, $coordinator, ['title' => 'Welcome thread']);

    Livewire::actingAs($coordinator)
        ->test(ProjectForumPage::class, ['project' => $project])
        ->assertSee('Welcome thread');
});

it('creates a thread and opens it', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectForumPage::class, ['project' => $project])
        ->set('title', 'A brand new thread')
        ->set('content', 'With some content here')
        ->call('createThread')
        ->assertHasNoErrors();

    $thread = $project->threads()->sole();
    expect($thread->title)->toBe('A brand new thread');
});

it('validates the create form', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectForumPage::class, ['project' => $project])
        ->set('title', 'no')
        ->call('createThread')
        ->assertHasErrors(['title']);
});

it('scopes threads to their project', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $otherThread = makeThread(makeProject($coordinator), $coordinator);

    $this->actingAs($coordinator)
        ->get(route('project.thread', ['project' => $project, 'thread' => $otherThread]))
        ->assertNotFound();
});

it('shows the thread overlay on the thread route', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator, ['title' => 'Open me']);
    ThreadComment::factory()->create([
        'thread_id' => $thread->id,
        'author_id' => $coordinator->id,
        'content' => 'First comment!',
    ]);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->assertSee('Open me')
        ->assertSee('First comment!');
});

it('adds a comment to the thread', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->set('newComment', 'Hello thread!')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSee('Hello thread!');

    expect($thread->comments()->sole('content')->content['raw'])->toBe('Hello thread!');
});

it('lets the author edit their comment', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator);
    $comment = ThreadComment::factory()->create([
        'thread_id' => $thread->id,
        'author_id' => $coordinator->id,
    ]);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->call('editComment', $comment->id)
        ->set('editCommentContent', 'Edited content!')
        ->call('saveComment')
        ->assertHasNoErrors();

    expect($comment->fresh()->content['raw'])->toBe('Edited content!');
});

it('lets the author delete their comment', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator);
    $comment = ThreadComment::factory()->create([
        'thread_id' => $thread->id,
        'author_id' => $coordinator->id,
    ]);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->call('deleteComment', $comment->id);

    expect($comment->fresh())->toBeNull();
});

it('lets the author edit and delete the thread', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->call('edit')
        ->set('editTitle', 'Renamed thread')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    expect($thread->fresh()->title)->toBe('Renamed thread');

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->call('deleteThread')
        ->assertRedirect(route('project.forum', $project));

    expect($thread->fresh())->toBeNull();
});

it('paginates comments with load more', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = makeThread($project, $coordinator);
    ThreadComment::factory()->count(15)->create([
        'thread_id' => $thread->id,
        'author_id' => $coordinator->id,
    ]);

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->assertSee('Load more comments')
        ->call('loadMore')
        ->assertDontSee('Load more comments');
});
