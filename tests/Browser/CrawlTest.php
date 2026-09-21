<?php

use App\Models\Thread;

beforeEach(function () {
    $this->user = makeUser();
    // Reporting yourself is a 403 by design; the report pages need a target.
    $this->other = makeUser();
    $this->project = makeProject($this->user, ['name' => 'Crawl Project']);
    $this->group = makeGroup($this->project, 1, ['name' => 'Crawl Group']);
    $this->task = makeTask($this->group, 1, ['name' => 'Crawl Task']);
    $this->thread = Thread::factory()->create([
        'project_id' => $this->project->id,
        'author_id' => $this->user->id,
        'title' => 'Crawl Thread',
    ]);

    $this->paths = [
        'home' => ['/', null],
        'project list' => ['/project', 'Your projects'],
        'project create' => ['/project/new', 'Create your new idea!'],
        'board' => ["/project/{$this->project->id}/board", 'Crawl Group'],
        'info' => ["/project/{$this->project->id}/info", 'Crawl Project'],
        'members' => ["/project/{$this->project->id}/members", 'Members'],
        'tags' => ["/project/{$this->project->id}/tags", 'Tags'],
        'tasks' => ["/project/{$this->project->id}/tasks", 'Tasks'],
        'timeline' => ["/project/{$this->project->id}/timeline", null],
        'forum' => ["/project/{$this->project->id}/forum", 'Crawl Thread'],
        'task' => ["/project/{$this->project->id}/task/{$this->task->id}", 'Crawl Task'],
        'thread' => ["/project/{$this->project->id}/thread/{$this->thread->id}", 'Crawl Thread'],
        'project report' => ["/project/{$this->project->id}/report", 'Reporting Crawl Project'],
        'notifications' => ['/notifications', null],
        // Profile pages show the name in a readonly input's value, not as
        // visible text — assertSee can't reach it; the smoke trio covers them.
        'profile' => ["/user/{$this->user->id}", null],
        'profile edit' => ["/user/{$this->user->id}/edit", null],
        'user report' => ["/user/{$this->other->id}/report", "Reporting {$this->other->name}"],
        'about' => ['/about', null],
        'contacts' => ['/contacts', 'Contacts'],
        'faq' => ['/faq', null],
        'services' => ['/services', null],
    ];
});

// One login, then a navigate loop: the smoke trio is the real signal on the
// JS-rendered pages; markers only where blades have stable headings.
it('crawls the member pages without errors', function () {
    $page = visit('/login')
        ->fill('email', $this->user->email)
        ->fill('password', 'password123')
        ->submit();

    foreach ($this->paths as $name => [$path, $marker]) {
        $page = $page->navigate($path)
            ->assertNoSmoke()
            ->assertNoJavaScriptErrors()
            ->assertNoConsoleLogs();

        if ($marker !== null) {
            $page->assertSee($marker);
        }
    }
});

it('crawls the admin pages without errors', function () {
    $admin = makeUser(['is_admin' => true]);

    $page = visit('/login')
        ->fill('email', $admin->email)
        ->fill('password', 'password123')
        ->submit();

    foreach ([
        '/admin/users',
        '/admin/projects',
        '/admin/create/user',
        "/admin/reports/user/{$this->user->id}",
        "/admin/reports/project/{$this->project->id}",
    ] as $path) {
        $page = $page->navigate($path)
            ->assertNoSmoke()
            ->assertNoJavaScriptErrors()
            ->assertNoConsoleLogs();
    }
});
