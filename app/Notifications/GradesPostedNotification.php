<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\SchoolClass;

class GradesPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $student,
        public readonly SchoolClass $class,
        public readonly string $assessmentType,
        public readonly float $score,
        public readonly float $maxScore,
        public readonly float $percentage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->class->loadMissing(['subject', 'grade', 'stream']);

        $subject    = $this->class->subject->name;
        $className  = $this->class->grade->name . ' ' . $this->class->stream->name;
        $grade      = $this->getLetterGrade($this->percentage);

        return (new MailMessage)
            ->subject("Grades posted for {$this->student->name} – {$subject}")
            ->greeting("Dear {$notifiable->name},")
            ->line("New grades have been posted for your child **{$this->student->name}**:")
            ->line("**Subject:** {$subject}")
            ->line("**Class:** {$className}")
            ->line("**Assessment:** {$this->assessmentType}")
            ->line("**Score:** {$this->score} / {$this->maxScore} ({$this->percentage}%) — Grade: {$grade}")
            ->line("Log in to the parent portal to view the full report card.")
            ->salutation('Regards, School Administration');
    }

    private function getLetterGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'A',
            $percentage >= 75 => 'A-',
            $percentage >= 70 => 'B+',
            $percentage >= 65 => 'B',
            $percentage >= 60 => 'B-',
            $percentage >= 55 => 'C+',
            $percentage >= 50 => 'C',
            $percentage >= 45 => 'C-',
            $percentage >= 40 => 'D+',
            $percentage >= 35 => 'D',
            $percentage >= 30 => 'D-',
            default           => 'E',
        };
    }
}
