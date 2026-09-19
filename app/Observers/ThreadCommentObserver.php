<?php

namespace App\Observers;

use App\Models\ThreadComment;
use Illuminate\Validation\ValidationException;

// Port of the validate_thread_comment_author PL/pgSQL trigger.
class ThreadCommentObserver
{
    public function creating(ThreadComment $comment): void
    {
        $member = $comment->thread->project->users()
            ->where('user_profile_id', $comment->author_id)
            ->exists();
        if (! $member) {
            throw ValidationException::withMessages([
                'author' => 'Thread comment author must be a member of the thread\'s project!',
            ]);
        }
    }
}
