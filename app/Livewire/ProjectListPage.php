<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProjectListPage extends Component
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
        $this->authorize('viewAny', Project::class);

        $searchTerm = request()->query('q') ?? '';

        $projects = request()->user()->projects();

        if ($searchTerm !== '') {
            $projects = $projects->searchText($searchTerm);
        }

        return view('livewire.project-list-page', [
            'projects' => $projects->paginate(10)->withQueryString(),
        ])->title('Your projects');
    }
}
