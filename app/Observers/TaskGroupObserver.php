<?php

namespace App\Observers;

use App\Models\TaskGroup;

// Port of the task_group reorder/close-gap PL/pgSQL triggers. The schema
// dropped the original DEFERRABLE uniques (sqlite checks unique indexes
// per row), so the moved row is parked at a temporary negative position
// and siblings are stepped one by one in a collision-free order. Mass
// query-builder updates deliberately skip model events (no recursion).
// Callers operate on freshly loaded models: mass steps do not sync
// in-memory instances, and stale positions shift the wrong range.
class TaskGroupObserver {

    public function updating(TaskGroup $taskGroup): void {
        if (!$taskGroup->isDirty('position')) {
            return;
        }
        $old = (int) $taskGroup->getOriginal('position');
        $new = (int) $taskGroup->position;
        if ($old === $new) {
            return;
        }
        $projectId = (int) $taskGroup->getOriginal('project_id');
        TaskGroup::whereKey($taskGroup->id)->update(['position' => -$taskGroup->id]);
        // Parking the attribute and restoring it keeps the final position
        // in the dirty set even when it equals the stale original.
        $taskGroup->setAttribute('position', -$taskGroup->id);
        if ($old > $new) {
            $this->step($projectId, [$new, $old - 1], 'desc', 'increment');
        } else {
            $this->step($projectId, [$old + 1, $new], 'asc', 'decrement');
        }
        $taskGroup->setAttribute('position', $new);
    }

    public function deleted(TaskGroup $taskGroup): void {
        $this->step((int) $taskGroup->project_id, [$taskGroup->position + 1, null], 'asc', 'decrement');
    }

    private function step(int $projectId, array $range, string $order, string $direction): void {
        $query = TaskGroup::where('project_id', $projectId)->where('position', '>=', $range[0]);
        if ($range[1] !== null) {
            $query->where('position', '<=', $range[1]);
        }
        foreach ($query->orderBy('position', $order)->pluck('id') as $id) {
            TaskGroup::whereKey($id)->{$direction}('position');
        }
    }
}
