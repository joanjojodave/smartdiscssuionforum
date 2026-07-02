<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Group $group,
        public bool $accepted,
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
        $message = (new MailMessage)->subject('Group membership: '.$this->group->name);

        return $this->accepted
            ? $message->line("Welcome! You have accepted the rules of \"{$this->group->name}\" and are now a member.")
                ->action('Open group', url('/groups/'.$this->group->id))
            : $message->line("Your request to join \"{$this->group->name}\" was declined because the platform rules were not accepted.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_join_decision',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'accepted' => $this->accepted,
            'message' => $this->accepted
                ? "You joined \"{$this->group->name}\"."
                : "Your request to join \"{$this->group->name}\" was declined.",
        ];
    }
}
