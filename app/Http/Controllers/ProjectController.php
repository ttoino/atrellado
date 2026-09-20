<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreReportRequest;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function showProjectBoard(Request $request, Project $project)
    {

        $this->authorize('view', $project);

        return $request->wantsJson()
            ? response()->json($project)
            : response()->view('pages.project.board', ['project' => $project]);
    }

    public function showProjectTimeline(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return $request->wantsJson()
            ? response()->json($project)
            : response()->view('pages.project.tbd', ['project' => $project]);
    }

    public function showProjectForum(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return $request->wantsJson()
            ? response()->json($project)
            : response()->view('pages.project.forum', ['project' => $project]);
    }

    public function joinProject(Request $request, Project $project)
    {

        $this->authorize('joinProject', $project);

        $user = User::findOrFail($request->query('user'));

        $project->users()->save($user);

        return redirect()->route('project', ['project' => $project]);
    }

    public function create()
    {

        $this->authorize('create', Project::class);

        return view('pages.project.new');
    }

    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);

        $project = $this->createProject($request);

        return $request->wantsJson()
            ? response()->json($project, 201)
            : redirect()->route('project', ['project' => $project]);
    }

    /**
     * Creates a new project.
     *
     * @return Project The project created.
     */
    public function createProject(Request $request)
    {

        $project = new Project;
        $data = $request->all();

        $project->name = $data['name'];
        $project->archived = false;
        $project->description = $data['description'];
        $project->coordinator_id = $request->user()->id;
        $project->save();

        return $project;
    }

    public function toggleFavorite(Request $request, Project $project)
    {

        $this->authorize('toggleFavorite', $project);

        $member = $project->users()->get()->first(fn (User $user) => $user->id === $request->user()->id);

        // Pivot columns are runtime-magic on the related model; fetch the
        // loaded pivot relation directly to keep static analysis happy.
        $pivot = $member->getRelation('pivot');

        $pivot->is_favorite = ! $pivot->is_favorite;
        $pivot->save();

        return response()->json(['isFavorite' => $pivot->is_favorite]);
    }

    public function destroy(Request $request, Project $project)
    {

        $this->authorize('delete', $project);
        $project->delete();

        return $request->wantsJson()
            ? response()->json($project)
            : redirect()->route('project.list');
    }

    protected function escape_like(string $value, string $char = '\\')
    {
        return str_replace(
            [$char, '%', '_'],
            [$char.$char, $char.'%', $char.'_'],
            $value
        );
    }

    public function getProjectTasks(Request $request, Project $project)
    {

        $this->authorize('getProjectTasks', $project);

        $searchTerm = $request->query('q') ?? '';

        $tasks = $this->searchTasks($searchTerm, $project)->withQueryString();

        return $request->wantsJson()
            ? response()->json($tasks)
            : response()->view('pages.project.tasks', ['tasks' => $tasks]);
    }

    public function searchTasks(string $searchTerm, Project $project)
    {

        $projectTasks = $project->tasks();

        if (! empty($searchTerm)) {
            $projectTasks = $projectTasks->searchText($searchTerm);
        }

        return $projectTasks->cursorPaginate(10);
    }

    public function getProjectTags(Request $request, Project $project)
    {
        $this->authorize('getProjectTags', $project);

        $searchTerm = $request->query('q') ?? '';

        $tags = $this->searchTags($project, $searchTerm)->withQueryString();

        return $request->wantsJson()
            ? response()->json($tags)
            : response()->view('pages.project.tags', ['tags' => $tags]);
    }

    public function searchTags(Project $project, string $search)
    {
        $members = $project->tags();

        if (! empty($search)) {
            $members = $members->where('title', 'like', '%'.ProjectController::escape_like($search).'%');
        }

        return $members->cursorPaginate(10);
    }

    public function showReportForm(Project $project)
    {
        $this->authorize('report', $project);

        return view('pages.reportproject', ['project' => $project]);
    }

    public function report(StoreReportRequest $request, Project $project)
    {

        $requestData = $request->all();

        $this->authorize('report', $project);

        $report = new Report;

        $report->reason = $requestData['reason'];
        $report->project_id = $project->id;
        $report->creator_id = $request->user()->id;
        $report->save();

        return redirect()->route('project', ['project' => $project]);
    }
}
