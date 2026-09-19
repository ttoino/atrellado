<?php

namespace App\Notifications;

use App\Models\ThreadComment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThreadCommented extends Notification
{
    public ThreadComment $thread_comment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ThreadComment $comment)
    {
        $this->thread_comment = $comment;
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
            ->line($this->thread_comment->author->name.'has commented on a thread you opened in '.$this->thread_comment->thread->project->name.'.')
            ->action('View the thread', route('project.thread', ['project' => $this->thread_comment->thread->project, 'thread' => $this->thread_comment->thread]))
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
        $thread = $this->thread_comment->thread;
        $project = $thread->project;

        return [
            'comment_id' => $this->thread_comment->id,
            'thread_id' => $thread->id,
            'thread_title' => $thread->title,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'author_name' => $this->thread_comment->author->name,
            'url' => route('project.thread', ['project' => $project, 'thread' => $thread]),
        ];
    }
}
