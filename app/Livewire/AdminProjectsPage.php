<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminProjectsPage extends Component
{
    public function toggleFavorite(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('toggleFavorite', $project);

        $member = $project->users()->get()->first(fn (User $user) => $user->id === request()->user()->id);

        // Pivot columns are runtime-magic on the related model; fetch the
        // loaded pivot relation directly to keep static analysis happy.
        $pivot = $member->getRelation('pivot');

        $pivot->is_favorite = ! $pivot->is_favorite;
        $pivot->save();
    }

    public function deleteProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('delete', $project);

        $project->delete();
    }

    public function render(): View
    {
        $searchTerm = request()->query('q') ?? '';

        $projects = Project::with('reports');

        if ($searchTerm !== '') {
            $projects = $projects->searchText($searchTerm);
        }

        return view('livewire.admin-projects-page', [
            'projects' => $projects->cursorPaginate(10)->withQueryString(),
        ])->title('Projects');
    }
}
