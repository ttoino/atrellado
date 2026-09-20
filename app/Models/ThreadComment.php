<?php

namespace App\Models;

use App\Casts\Datetime;
use App\Casts\Markdown;
use App\Events\ThreadCommentCreated;
use App\Observers\ThreadCommentObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadComment extends Model
{
    use HasFactory;

    // Author-membership validation ported from the PL/pgSQL triggers.
    protected static function booted(): void
    {
        static::observe(ThreadCommentObserver::class);
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

    protected $casts = [
        'creation_date' => Datetime::class,
        'edit_date' => Datetime::class,
        'content' => Markdown::class,
    ];

    protected $dispatchesEvents = [
        'created' => ThreadCommentCreated::class,
    ];

    /** @return BelongsTo<Thread, $this> */
    public function thread()
    {
        return $this->belongsTo(
            Thread::class,
            'thread_id'
        );
    }

    /** @return BelongsTo<User, $this> */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault(User::DELETED_USER);
    }

    protected $table = 'thread_comment';
}
