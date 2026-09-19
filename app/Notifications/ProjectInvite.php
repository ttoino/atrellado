<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectInvite extends Notification implements ShouldQueue
{
    use Queueable;

    public string $url;

    public Project $project;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $url, Project $project)
    {
        $this->url = $url;
        $this->project = $project;
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
            'mail',
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
            ->line("You've been invited to join ".$this->project->name.'.')
            ->action('Join this project', url($this->url))
            ->line('If you think this was not intended for you or if you do not have interest in joining this project, please ignore this message.');
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
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'url' => $this->url,
        ];
    }
}
