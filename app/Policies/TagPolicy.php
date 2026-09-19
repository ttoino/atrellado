<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TagPolicy
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
    public function view(User $user, Tag $tag)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->allow();
        }

        if ($tag->project->users->contains($user)) {
            return $this->allow();
        }

        return $this->deny('Only admins or a member of the tag\'s project can view this tag');
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
            return $this->deny('Admins cannot create tags');
        }

        if (! $project->users->contains($user)) {
            return $this->deny('To create a tag in this project you must be a member of this project');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, Tag $tag)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot edit tags');
        }

        if (! $tag->project->users->contains($user)) {
            return $this->deny('To edit a tag you must be a member of its project');
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, Tag $tag)
    {

        if ($user->blocked) {
            return $this->deny('Your user account has been blocked');
        }

        if ($user->is_admin) {
            return $this->deny('Admins cannot delete tags');
        }

        if (! $tag->project->users->contains($user)) {
            return $this->deny('To delete a tag you must be a member of its project');
        }

        return $this->allow();
    }
}
