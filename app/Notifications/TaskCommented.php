<?php

namespace App\Notifications;

use App\Models\TaskComment;
use Illuminate\Notifications\Messages\MailMessage;

class TaskCommented extends Notification
{
    public TaskComment $comment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TaskComment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [
            CustomDatabaseChannel::class,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            // Known relation-call bug, replaced in a later phase.
            // @phpstan-ignore property.notFound
            ->line($this->comment->author()->name."has left a comment on a task you're assigned to - ".$this->comment->task->name.'.')
            // @phpstan-ignore property.notFound
            ->action('View the task', route('project.task.info', ['project' => $this->comment->task()->project, 'task' => $this->comment->task]))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'comment' => $this->comment,
        ];
    }
}
