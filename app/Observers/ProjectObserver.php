<?php

namespace App\Observers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

// Port of the coordinator-membership PL/pgSQL triggers. insertOrIgnore
// keeps a coordinator who is already an explicit member untouched.
class ProjectObserver {

    public function created(Project $project): void {
        DB::table('project_member')->insertOrIgnore([
            'user_profile_id' => $project->coordinator_id,
            'project_id' => $project->id,
            'is_favorite' => false,
        ]);
    }

    public function updating(Project $project): void {
        if (!$project->isDirty('coordinator_id')) {
            return;
        }
        DB::table('project_member')
            ->where('project_id', $project->id)
            ->where('user_profile_id', $project->getOriginal('coordinator_id'))
            ->delete();
        DB::table('project_member')->insertOrIgnore([
            'user_profile_id' => $project->coordinator_id,
            'project_id' => $project->id,
            'is_favorite' => false,
        ]);
    }
}
