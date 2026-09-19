<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Casts\Markdown;
use App\Events\ThreadCreated;
use App\Observers\ThreadObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    use HasFactory;

    // Author-membership validation ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(ThreadObserver::class);
    }

    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'edit_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $with = ['author'];

    protected $casts = [
        'creation_date' => Datetime::class,
        'edit_date' => Datetime::class,
        'content' => Markdown::class,
    ];

    protected $dispatchesEvents = [
        'created' => ThreadCreated::class,
    ];

    /** @return BelongsTo<Project, $this> */
    public function project()
    {
        return $this->belongsTo(
            Project::class,
            'project_id'
        );
    }

    /** @return HasMany<ThreadComment, $this> */
    public function comments()
    {
        return $this->hasMany(
            ThreadComment::class,
            'thread_id'
        );
    }

    /** @return BelongsTo<User, $this> */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault(User::DELETED_USER);
    }

    protected $table = 'thread';
}
