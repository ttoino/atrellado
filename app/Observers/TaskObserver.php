<?php

namespace App\Observers;

use App\Models\Task;

// Port of the task reorder/cross-group/close-gap PL/pgSQL triggers. The
// schema dropped the original DEFERRABLE uniques (sqlite checks unique
// indexes per row), so the moved row is parked at a temporary negative
// position and siblings are stepped one by one in a collision-free
// order. Mass query-builder updates deliberately skip model events.
// Callers operate on freshly loaded models: mass steps do not sync
// in-memory instances, and stale positions shift the wrong range.
class TaskObserver {

    public function updating(Task $task): void {
        if ($task->isDirty('task_group_id')) {
            $oldGroup = (int) $task->getOriginal('task_group_id');
            $oldPosition = (int) $task->getOriginal('position');
            $newGroup = (int) $task->task_group_id;
            $newPosition = (int) $task->position;
            Task::whereKey($task->id)->update(['position' => -$task->id]);
            $task->setAttribute('position', -$task->id);
            $this->step($oldGroup, [$oldPosition + 1, null], 'asc', 'decrement');
            $this->step($newGroup, [$newPosition, null], 'desc', 'increment');
            $task->setAttribute('position', $newPosition);
            return;
        }
        if (!$task->isDirty('position')) {
            return;
        }
        $old = (int) $task->getOriginal('position');
        $new = (int) $task->position;
        if ($old === $new) {
            return;
        }
        $groupId = (int) $task->task_group_id;
        Task::whereKey($task->id)->update(['position' => -$task->id]);
        $task->setAttribute('position', -$task->id);
        if ($old > $new) {
            $this->step($groupId, [$new, $old - 1], 'desc', 'increment');
        } else {
            $this->step($groupId, [$old + 1, $new], 'asc', 'decrement');
        }
        $task->setAttribute('position', $new);
    }

    public function deleted(Task $task): void {
        $this->step((int) $task->task_group_id, [(int) $task->position + 1, null], 'asc', 'decrement');
    }

    private function step(int $groupId, array $range, string $order, string $direction): void {
        $query = Task::where('task_group_id', $groupId)->where('position', '>=', $range[0]);
        if ($range[1] !== null) {
            $query->where('position', '<=', $range[1]);
        }
        foreach ($query->orderBy('position', $order)->pluck('id') as $id) {
            Task::whereKey($id)->{$direction}('position');
        }
    }
}
