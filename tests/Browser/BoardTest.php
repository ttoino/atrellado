<?php

// The playwright client only connects at test-body start, so visit() calls
// belong in the tests themselves, not beforeEach.
function boardLogin(object $t): mixed
{
    return visit('/login')
        ->fill('email', $t->user->email)
        ->fill('password', 'password123')
        ->submit()
        ->navigate("/project/{$t->project->id}/board")
        // Elements are clickable before their handlers attach; the retried
        // assertion waits for the board's enhancement pass to mark them.
        ->assertScript("document.querySelector('.task-group[data-task-group-id][data-enhanced]') !== null");
}

beforeEach(function () {
    $this->user = makeUser();
    $this->project = makeProject($this->user, ['name' => 'Board Project']);
    $this->todo = makeGroup($this->project, 1, ['name' => 'To Do']);
    $this->done = makeGroup($this->project, 2, ['name' => 'Done']);
    $this->task = makeTask($this->todo, 1, ['name' => 'Drag Me']);
});

// Group names render inside editable textareas; their values are invisible
// to text assertions, so visibility is checked via script.
it('creates a task group', function () {
    boardLogin($this)
        ->fill('#new-task-group-form [name=name]', 'Browser Group')
        ->press('#new-task-group-form [type=submit]')
        ->assertScript("Array.from(document.querySelectorAll('.task-group textarea[name=name]')).some(t => t.value === 'Browser Group')");

    $this->assertDatabaseHas('task_group', [
        'project_id' => $this->project->id,
        'name' => 'Browser Group',
    ]);
});

it('creates and edits a task', function () {
    $page = boardLogin($this)
        ->fill(".task-group[data-task-group-id=\"{$this->todo->id}\"] .new-task-form [name=name]", 'Fresh Task')
        ->press(".task-group[data-task-group-id=\"{$this->todo->id}\"] .new-task-form [type=submit]")
        ->waitForText('Fresh Task');

    $this->assertDatabaseHas('task', ['name' => 'Fresh Task']);

    // Clicking the card navigates to the task page (offcanvas in read mode);
    // the edit form is display:none until #edit-task-button toggles it.
    $page
        ->click('Fresh Task')
        ->click('#edit-task-button')
        ->fill('#edit-task-form [name=name]', 'Renamed Task')
        ->press('#edit-task-form [type=submit]')
        ->waitForText('Renamed Task');

    $this->assertDatabaseHas('task', ['name' => 'Renamed Task']);
});

it('moves a task between groups via drag and drop', function () {
    // Sortable is configured with handle: ".grip" — drags must start there.
    $page = boardLogin($this);

    $page->drag(
        ".task[data-task-id=\"{$this->task->id}\"] .grip",
        ".task-group[data-task-group-id=\"{$this->done->id}\"] > ul",
    );

    // The reposition PUT is fire-and-forget; a fresh render proves the
    // persisted state (and exercises the read path).
    $page
        ->navigate("/project/{$this->project->id}/board")
        ->assertScript("document.querySelector('.task[data-task-id=\"{$this->task->id}\"]')?.closest('.task-group')?.dataset.taskGroupId === \"{$this->done->id}\"");
});

it('deletes an empty task group', function () {
    boardLogin($this)
        ->click(".task-group[data-task-group-id=\"{$this->done->id}\"] .delete-task-group")
        // Deletion is optimistic: the element is removed before the DELETE
        // roundtrip lands, so persistence is checked on a fresh render.
        ->navigate("/project/{$this->project->id}/board")
        ->assertScript("Array.from(document.querySelectorAll('.task-group textarea[name=name]')).some(t => t.value === 'Done')", false);
});
