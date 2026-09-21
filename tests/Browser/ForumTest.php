<?php

use App\Models\Thread;

// See BoardTest for why visit() lives in a helper rather than beforeEach.
function forumLogin(object $t, string $path): mixed
{
    return visit('/login')
        ->fill('email', $t->user->email)
        ->fill('password', 'password123')
        ->submit()
        ->navigate($path)
        // Elements are clickable before livewire boots; the retried
        // assertion waits for it.
        ->assertScript('window.Livewire !== undefined');
}

beforeEach(function () {
    $this->user = makeUser();
    $this->project = makeProject($this->user, ['name' => 'Forum Project']);
    $this->thread = Thread::factory()->create([
        'project_id' => $this->project->id,
        'author_id' => $this->user->id,
        'title' => 'Existing Thread',
    ]);
});

it('creates a thread', function () {
    forumLogin($this, "/project/{$this->project->id}/forum")
        // The create offcanvas renders after a livewire roundtrip.
        ->click('New Thread')
        ->fill('#new-thread-title', 'Browser Thread')
        ->fill('#new-thread-content', 'Opening post from a browser')
        ->press('Create thread')
        ->waitForText('Browser Thread')
        ->assertSee('Browser Thread');

    $this->assertDatabaseHas('thread', [
        'project_id' => $this->project->id,
        'title' => 'Browser Thread',
    ]);
});

it('opens the thread overlay from the list', function () {
    forumLogin($this, "/project/{$this->project->id}/forum")
        ->click('Existing Thread')
        ->assertPathIs("/project/{$this->project->id}/thread/{$this->thread->id}")
        ->assertSee('Existing Thread');
});

it('posts a comment and renders its markdown', function () {
    forumLogin($this, "/project/{$this->project->id}/thread/{$this->thread->id}")
        ->fill('#thread-comment-new', 'A **bold** statement')
        ->press('#thread-comment-new ~ [type=submit]')
        ->assertScript("Array.from(document.querySelectorAll('.thread-comment strong')).some(s => s.textContent === 'bold')");

    $this->assertDatabaseHas('thread_comment', ['content' => 'A **bold** statement']);
});
