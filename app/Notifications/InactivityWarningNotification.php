<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InactivityWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Group $group,
        public int $warningNumber,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Inactivity warning ({$this->warningNumber}/2) - {$this->group->name}")
            ->line("You have been inactive in the group \"{$this->group->name}\" for a while.")
            ->line("This is warning {$this->warningNumber} of 2. A third warning will result in a temporary blacklist.")
            ->action('Go to group', url('/groups/'.$this->group->id))
            ->line('Post or reply to a discussion to reset your activity status.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inactivity_warning',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'warning_number' => $this->warningNumber,
            'message' => "Inactivity warning {$this->warningNumber}/2 in \"{$this->group->name}\".",
        ];
    }
}
