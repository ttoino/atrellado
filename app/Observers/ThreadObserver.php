<?php

namespace App\Observers;

use App\Models\Thread;
use Illuminate\Validation\ValidationException;

// Port of the validate_thread_author PL/pgSQL trigger.
class ThreadObserver {

    public function creating(Thread $thread): void {
        $member = $thread->project->users()
            ->where('user_profile_id', $thread->author_id)
            ->exists();
        if (!$member) {
            throw ValidationException::withMessages([
                'author' => 'Thread author must be a member of the thread\'s project!',
            ]);
        }
    }
}
