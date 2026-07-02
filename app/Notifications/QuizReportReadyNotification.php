<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizReportReadyNotification extends Notification
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
            ->subject("Quiz results ready: {$this->quiz->title}")
            ->line("The quiz \"{$this->quiz->title}\" has closed and the performance report is available.")
            ->action('View report', url('/quizzes/'.$this->quiz->id.'/report'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quiz_report_ready',
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'message' => "Performance report ready for \"{$this->quiz->title}\".",
        ];
    }
}
