<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class WithOtherProjects
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        // we had an issue where rendering views directly was causing errors to be thrown because of Laravel's implicit binding system
        // this should now be 'useless' but keep it as a fail-safe
        $routeProject = $request->route('project');
        $project = is_string($routeProject) ? Project::findOrFail($routeProject) : $routeProject;

        View::share('project', $project);
        View::share('other_projects', $request->user()?->projects()->whereKeyNot($project->id)->simplePaginate(5));

        return $next($request);
    }
}
