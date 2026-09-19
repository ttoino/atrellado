<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, TaskGroup $taskGroup)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if (! $user->is_admin && ! $taskGroup->project->users->contains($user)) {
            return $this->deny('Only admins or members of this group\'s project can view info on this task group');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user, Project $project)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot create task groups');
        }

        if (! $project->users->contains($user)) {
            return $this->deny('Only members of this given project can create task groups');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, TaskGroup $taskGroup)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot update task groups');
        }

        if (! $taskGroup->project->users->contains($user)) {
            return $this->deny('Only members of this group\'s project can update task groups');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, TaskGroup $taskGroup)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot delete task groups');
        }

        if (! $taskGroup->project->users->contains($user)) {
            return $this->deny('Only members of this group\'s project can update task groups');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, TaskGroup $taskGroup)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, TaskGroup $taskGroup)
    {
        return false;
    }
}
