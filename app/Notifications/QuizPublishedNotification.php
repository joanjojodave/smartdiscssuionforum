<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Quiz $quiz,
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
            ->subject("New quiz scheduled: {$this->quiz->title}")
            ->line("A new quiz \"{$this->quiz->title}\" has been scheduled in \"{$this->quiz->group->name}\".")
            ->line('Starts at: '.$this->quiz->start_at->toDayDateTimeString())
            ->line('Duration: '.$this->quiz->duration_minutes.' minutes')
            ->action('View announcement', url('/quizzes/'.$this->quiz->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quiz_published',
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'start_at' => $this->quiz->start_at->toIso8601String(),
            'message' => "New quiz \"{$this->quiz->title}\" starts {$this->quiz->start_at->toDayDateTimeString()}.",
        ];
    }
}
