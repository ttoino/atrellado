<?php

namespace App\Observers;

use App\Models\TaskComment;
use Illuminate\Validation\ValidationException;

// Port of the validate_task_comment_author PL/pgSQL trigger.
class TaskCommentObserver {

    public function creating(TaskComment $comment): void {
        $member = $comment->task->project->users()
            ->where('user_profile_id', $comment->author_id)
            ->exists();
        if (!$member) {
            throw ValidationException::withMessages([
                'author' => 'Task comment author must be a member of the task\'s project!',
            ]);
        }
    }
}
