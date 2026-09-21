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
        // Elements are clickable before livewire boots and sortable
        // attaches; the retried assertions wait for both.
        ->assertScript("window.Livewire !== undefined")
        ->assertScript("document.querySelector('[data-board][data-enhanced]') !== null");
}

beforeEach(function () {
    $this->user = makeUser();
    $this->project = makeProject($this->user, ['name' => 'Board Project']);
    $this->todo = makeGroup($this->project, 1, ['name' => 'To Do']);
    $this->done = makeGroup($this->project, 2, ['name' => 'Done']);
    $this->task = makeTask($this->todo, 1, ['name' => 'Drag Me']);
});

// Group names render inside editable textareas (no name attributes on
// livewire); their values are invisible to text assertions, so visibility
// is checked via script.
it('creates a task group', function () {
    boardLogin($this)
        ->fill('#new-task-group-form textarea', 'Browser Group')
        ->press('#new-task-group-form [type=submit]')
        ->assertScript("Array.from(document.querySelectorAll('.task-group .edit-task-group-form textarea')).some(t => t.value === 'Browser Group')");

    $this->assertDatabaseHas('task_group', [
        'project_id' => $this->project->id,
        'name' => 'Browser Group',
    ]);
});

it('creates and edits a task', function () {
    $page = boardLogin($this)
        ->fill(".task-group[data-task-group-id=\"{$this->todo->id}\"] .new-task-form textarea", 'Fresh Task')
        ->press(".task-group[data-task-group-id=\"{$this->todo->id}\"] .new-task-form [type=submit]")
        ->waitForText('Fresh Task');

    $this->assertDatabaseHas('task', ['name' => 'Fresh Task']);

    // The card deep-links to the task overlay page; its read mode swaps to
    // the edit form via the Edit button.
    $page
        ->click('Fresh Task')
        ->click('Edit')
        ->fill('#task-name', 'Renamed Task')
        ->press('Save changes')
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

    // The task-moved request is queued behind livewire's commit pipeline, so
    // navigating immediately would tear it down; wait for it to land, then
    // let a fresh render prove the persisted state.
    waitForPhp($page, fn () => DB::table('task')->find($this->task->id)?->task_group_id === $this->done->id);

    $page
        ->navigate("/project/{$this->project->id}/board")
        ->assertScript("document.querySelector('.task[data-task-id=\"{$this->task->id}\"]')?.closest('.task-group')?.dataset.taskGroupId === \"{$this->done->id}\"");
});

it('deletes an empty task group', function () {
    $page = boardLogin($this);

    // wire:confirm guards the delete behind window.confirm, which playwright
    // auto-dismisses; force-accept it first.
    $page->script('window.confirm = () => true');

    $page
        ->click(".task-group[data-task-group-id=\"{$this->done->id}\"] .delete-task-group")
        ->assertScript("Array.from(document.querySelectorAll('.task-group .edit-task-group-form textarea')).some(t => t.value === 'Done')", false);

    $this->assertDatabaseMissing('task_group', ['id' => $this->done->id]);
});
