<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectInvite;
use App\Notifications\ProjectRemoved;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProjectMembersPage extends Component
{
    public Project $project;

    public string $email = '';

    public function mount(Project $project): void
    {
        $this->authorize('getProjectMembers', $project);

        $this->project = $project;
    }

    public function removeUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('removeUser', [$this->project, $user]);

        $this->project->users()->detach($user);

        $user->notify(new ProjectRemoved($this->project));
    }

    public function setCoordinator(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('setCoordinator', [$this->project, $user]);

        $this->project->coordinator_id = $user->id;
        $this->project->save();
    }

    public function invite(): void
    {
        $validated = $this->validate([
            'email' => 'required|email|min:6|max:50',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => 'No user with that email address exists.',
            ]);
        }

        $response = Gate::inspect('addUser', [$this->project, $user]);

        if ($response->denied()) {
            throw ValidationException::withMessages([
                'email' => $response->message(),
            ]);
        }

        $url = URL::signedRoute('project.join', ['project' => $this->project, 'user' => $user]);

        $user->notify(new ProjectInvite($url, $this->project));

        $this->reset('email');

        $this->dispatch('toast', text: 'Invited user');
    }

    public function render(): View
    {
        $searchTerm = request()->query('q') ?? '';

        $members = $this->project->users();

        if ($searchTerm !== '') {
            $members = $members->where('name', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm).'%');
        }

        return view('livewire.project-members-page', [
            'members' => $members->cursorPaginate(10)->withQueryString(),
        ])->layout('layouts.project', ['project' => $this->project])
            ->title($this->project->name);
    }
}
