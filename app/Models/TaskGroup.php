<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Observers\TaskGroupObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskGroup extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Sibling-position bookkeeping ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(TaskGroupObserver::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'position',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $casts = [
        'creation_date' => Datetime::class,
    ];

    /** @return BelongsTo<Project, $this> */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks()
    {
        return $this->hasMany(
            Task::class,
            'task_group_id'
        )->orderBy('position');
    }

    protected $table = 'task_group';
}
