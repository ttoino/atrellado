<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'color',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $appends = ['rgb_color'];

    /** @return BelongsTo<Project, $this> */
    public function project()
    {
        return $this->belongsTo(
            Project::class,
            'project_id'
        );
    }

    /** @return BelongsToMany<Task, $this> */
    public function tasks()
    {
        return $this->belongsToMany(
            Task::class,
            'task_tag',
            'tag_id',
            'task_id'
        );
    }

    /** @return Attribute<string, int> */
    protected function color(): Attribute
    {
        return Attribute::make(fn ($color) => sprintf('#%06x', $color));
    }

    protected function rgbColor(): Attribute
    {
        return Attribute::make(function ($_, $a) {
            $color = $a['color'] ?? 0;

            return ($color >> 16).', '.(($color >> 8) & 255).', '.($color & 255);
        });
    }

    protected $table = 'tag';
}
