<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Casts\Markdown;
use App\Concerns\SearchableText;
use App\Events\ProjectDeleted;
use App\Observers\ProjectObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory, SearchableText;

    // Coordinator-membership bookkeeping ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(ProjectObserver::class);
    }

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'edit_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'archived',
        'description',
        'coordinator_id',
        'edit_date',
    ];

    protected $casts = [
        'creation_date' => Datetime::class,
        'edit_date' => Datetime::class,
        'description' => Markdown::class,
    ];

    protected $dispatchesEvents = [
        'deleting' => ProjectDeleted::class,
    ];

    /** @return BelongsTo<User, $this> */
    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'project_member',
            'project_id',
            'user_profile_id'
        )->withPivot('is_favorite');
    }

    /** @return HasMany<TaskGroup, $this> */
    public function taskGroups()
    {
        return $this->hasMany(
            TaskGroup::class,
            'project_id'
        )->orderBy('position');
    }

    /** @return HasManyThrough<Task, TaskGroup, $this> */
    public function tasks()
    {
        return $this->hasManyThrough(
            Task::class,
            TaskGroup::class,
            'project_id',
            'task_group_id'
        );
    }

    /** @return HasMany<Tag, $this> */
    public function tags()
    {
        return $this->hasMany(
            Tag::class,
            'project_id'
        );
    }

    /** @return HasMany<Thread, $this> */
    public function threads()
    {
        return $this->hasMany(
            Thread::class,
            'project_id'
        )->orderBy('creation_date', 'desc');
    }

    /** @return HasMany<Report, $this> */
    public function reports()
    {
        return $this->hasMany(Report::class, 'project_id');
    }

    protected $table = 'project';
}
