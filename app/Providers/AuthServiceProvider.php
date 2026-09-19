<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\Thread;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\TaskGroupPolicy;
use App\Policies\TaskPolicy;
use App\Policies\ThreadPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        TaskGroup::class => TaskGroupPolicy::class,
        User::class => UserPolicy::class,
        Thread::class => ThreadPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('admin-action', function (User $user) {
            if (! $user->is_admin) {
                return Response::deny('Only an admin can perform this action');
            }

            return Response::allow();
        });

        Gate::define('viewPulse', fn (User $user) => $user->is_admin);
    }
}
