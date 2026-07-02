<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Post $reply,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New reply on: '.$this->reply->topic->title)
            ->line($this->reply->author->name.' replied to your question in "'.$this->reply->topic->title.'".')
            ->action('View discussion', url('/topics/'.$this->reply->topic_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_reply',
            'topic_id' => $this->reply->topic_id,
            'post_id' => $this->reply->id,
            'author' => $this->reply->author->name,
            'message' => $this->reply->author->name.' replied to "'.$this->reply->topic->title.'".',
        ];
    }
}
