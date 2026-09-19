<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Casts\Markdown;
use App\Concerns\SearchableText;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class Task extends Model
{
    use HasFactory, Notifiable, SearchableText;

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'edit_date';

    // Sibling-position bookkeeping ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(TaskObserver::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'edit_date',
        'completed',
        'task_group_id',
        'creator_id',
        'position',
    ];

    protected $casts = [
        'creation_date' => Datetime::class,
        'edit_date' => Datetime::class,
        'completed' => 'boolean',
        'description' => Markdown::class,
    ];

    protected $with = ['tags', 'creator', 'assignees'];

    public function project()
    {
        return $this->hasOneThrough(
            Project::class,
            TaskGroup::class,
            'id',
            'id',
            'task_group_id',
            'project_id'
        );
    }

    public function taskGroup()
    {
        return $this->belongsTo(
            TaskGroup::class,
            'task_group_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id')->withDefault(User::DELETED_USER);
    }

    public function comments()
    {
        return $this->hasMany(
            TaskComment::class,
            'task_id'
        );
    }

    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'task_tag',
            'task_id',
            'tag_id'
        );
    }

    public function assignees()
    {
        return $this->belongsToMany(
            User::class,
            'task_assignee',
            'task_id',
            'user_profile_id'
        );
    }

    // Backstops the old invalid_task_tag trigger; pivot inserts fire no
    // model events, so the check lives next to the attach.
    public function attachTag(Tag $tag): void
    {
        if ($tag->project_id !== $this->project->id) {
            throw ValidationException::withMessages([
                'tags' => 'Cannot apply tag to task of another project!',
            ]);
        }
        $this->tags()->save($tag);
    }

    // Backstops the old validate_assignee_project_member trigger.
    public function attachAssignee(User $assignee): void
    {
        if (! $this->project->users()->where('user_profile_id', $assignee->id)->exists()) {
            throw ValidationException::withMessages([
                'assignees' => 'Task assignee must be a member of the task\'s project!',
            ]);
        }
        $this->assignees()->save($assignee);
    }

    protected $table = 'task';
}
