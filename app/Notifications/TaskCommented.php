<?php

namespace App\Notifications;

use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommented extends Notification implements ShouldQueue
{
    use Queueable;

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
            'database',
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
            ->line($this->comment->author->name."has left a comment on a task you're assigned to - ".$this->comment->task->name.'.')
            ->action('View the task', route('project.task.info', ['project' => $this->comment->task->project, 'task' => $this->comment->task]))
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
        $task = $this->comment->task;
        $project = $task->project;

        return [
            'comment_id' => $this->comment->id,
            'task_id' => $task->id,
            'task_name' => $task->name,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'author_name' => $this->comment->author->name,
            'url' => route('project.task.info', ['project' => $project, 'task' => $task]),
        ];
    }
}
