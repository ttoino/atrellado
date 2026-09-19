<?php

namespace App\Models;

use App\Casts\Datetime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reason',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'creation_date' => Datetime::class,
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_profile_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id')->withDefault(User::DELETED_USER);
    }

    protected $table = 'report';
}
