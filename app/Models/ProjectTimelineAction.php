<?php

namespace App\Models;

use App\Casts\Datetime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimelineAction extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'description',
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

    protected $table = 'project_timeline_action';
}
