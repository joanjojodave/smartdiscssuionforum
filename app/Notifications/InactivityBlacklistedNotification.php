<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InactivityBlacklistedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Group $group,
        public Carbon $blacklistUntil,
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
            ->subject("You have been blacklisted from {$this->group->name}")
            ->line("After 3 missed activity warnings, you have been temporarily blacklisted from \"{$this->group->name}\".")
            ->line('You will automatically regain access on '.$this->blacklistUntil->toDayDateTimeString().'.')
            ->line('Participate regularly to avoid this in future.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inactivity_blacklisted',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'blacklist_until' => $this->blacklistUntil->toIso8601String(),
            'message' => "Blacklisted from \"{$this->group->name}\" until {$this->blacklistUntil->toDayDateTimeString()}.",
        ];
    }
}
