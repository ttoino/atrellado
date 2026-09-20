<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('can:admin-action')]
class AdminController extends Controller
{
    public function showCreateUser()
    {

        return response()->view('pages.admin.create.user');
    }

    public function createUser(StoreUserRequest $request)
    {

        User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ]);

        return redirect()->route('admin.users');
    }

    public function showUserReports(Request $request, User $user)
    {

        $reports = $user->reports()->cursorPaginate(10);

        return response()->view('pages.admin.reports.user', ['user' => $user, 'reports' => $reports]);
    }

    public function showProjectReports(Request $request, Project $project)
    {

        $reports = $project->reports()->cursorPaginate(10);

        return response()->view('pages.admin.reports.project', ['projects' => $project, 'reports' => $reports]);
    }
}
