<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\SchoolClass;
use Carbon\Carbon;

class StudentAbsentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $student,
        public readonly SchoolClass $class,
        public readonly string $status,
        public readonly Carbon $date,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->class->loadMissing(['subject', 'grade', 'stream']);

        $statusLabel = ucfirst($this->status);
        $subject     = $this->class->subject->name;
        $dateStr     = $this->date->format('l, F j, Y');
        $className   = $this->class->grade->name . ' ' . $this->class->stream->name;

        return (new MailMessage)
            ->subject("{$this->student->name} was marked {$statusLabel} – {$dateStr}")
            ->greeting("Dear {$notifiable->name},")
            ->line("This is to inform you that your child **{$this->student->name}** was marked **{$statusLabel}** in the following class:")
            ->line("**Subject:** {$subject}")
            ->line("**Class:** {$className}")
            ->line("**Date:** {$dateStr}")
            ->line("If you have any questions, please contact the school.")
            ->salutation('Regards, School Administration');
    }
}
