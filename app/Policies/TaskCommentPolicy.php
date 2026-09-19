<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskCommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user, Task $task)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->allow();
        }

        if (! $task->project->users->contains($user)) {
            return $this->deny('You need to be a member of the task\'s project in order to see its comments');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  TaskComment  $TaskComment
     * @return Response|bool
     */
    public function view(User $user, TaskComment $taskComment)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->allow();
        }

        if (! $taskComment->task->project->users->contains($user)) {
            return $this->deny('You must be a member of this comment\'s task\'s project to be able to see it');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user, Task $task)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot create task comments');
        }

        if (! $task->project->users->contains($user)) {
            return $this->deny('You must belong to task\'s project in order to create comments on this task');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  TaskComment  $TaskComment
     * @return Response|bool
     */
    public function update(User $user, TaskComment $taskComment)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot update task comments');
        }

        if ($taskComment->author->id !== $user->id) {
            return $this->deny('You need to be this comment\'s author in order to update it');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  TaskComment  $TaskComment
     * @return Response|bool
     */
    public function delete(User $user, TaskComment $taskComment)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot delete task comments');
        }

        if ($taskComment->author->id !== $user->id) {
            return $this->deny('You need to be this comment\'s author in order to delete it');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, TaskComment $TaskComment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, TaskComment $TaskComment)
    {
        //
    }
}
