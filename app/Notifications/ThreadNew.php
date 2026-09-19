<?php

namespace App\Notifications;

use App\Models\Thread;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThreadNew extends Notification
{
    public Thread $thread;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Thread $thread)
    {
        $this->thread = $thread;
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
            ->line($this->thread->author->name.'has opened a thread in project '.$this->thread->project->name.'.')
            ->action('View the thread', route('project.thread', ['project' => $this->thread->project, 'thread' => $this->thread]))
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
        $project = $this->thread->project;

        return [
            'thread_id' => $this->thread->id,
            'thread_title' => $this->thread->title,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'author_name' => $this->thread->author->name,
            'url' => route('project.thread', ['project' => $project, 'thread' => $this->thread]),
        ];
    }
}
