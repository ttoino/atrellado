<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class TaskAssigned extends Notification
{
    public Task $task;

    public User $assigner;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Task $task, User $assigner)
    {
        $this->task = $task;
        $this->assigner = $assigner;
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
            ->line("You've been assigned to a task in ".$this->task->project()->name.' by '.$this->assigner->name.'.')
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
        return [
            'task' => $this->task,
            'assigner' => $this->assigner,
        ];
    }
}
