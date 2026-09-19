<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Casts\Markdown;
use App\Events\TaskCommentCreated;
use App\Observers\TaskCommentObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    use HasFactory;

    // Author-membership validation ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(TaskCommentObserver::class);
    }

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'edit_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $with = ['author'];

    protected function casts(): array
    {
        return [
            'creation_date' => Datetime::class,
            'edit_date' => Datetime::class,
            'content' => Markdown::class,
        ];
    }

    protected $dispatchesEvents = [
        'created' => TaskCommentCreated::class,
    ];

    /** @return BelongsTo<Task, $this> */
    public function task()
    {
        return $this->belongsTo(
            Task::class,
            'task_id'
        );
    }

    /** @return BelongsTo<User, $this> */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault(User::DELETED_USER);
    }

    protected $table = 'task_comment';
}
