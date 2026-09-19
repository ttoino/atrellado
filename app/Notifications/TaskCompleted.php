<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
{
    public Task $task;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
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
            ->line("Task '".$this->task->name."' has been set as complete.")
            ->action('View the task', route('project.task.info', ['project' => $this->task->project, 'task' => $this->task]))
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
        $project = $this->task->project;

        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'url' => route('project.task.info', ['project' => $project, 'task' => $this->task]),
        ];
    }
}
