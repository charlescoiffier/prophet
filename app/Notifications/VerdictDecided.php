<?php

namespace App\Notifications;

use App\Enums\VerdictStatus;
use App\Models\Verdict;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Prévient le membre qui a proposé le verdict de la décision prise.
 */
class VerdictDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Verdict $verdict) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = $this->verdict->status === VerdictStatus::Approved ? 'verdict_approved' : 'verdict_rejected';

        $message = (new MailMessage)
            ->subject(__("notifications.{$key}.subject"))
            ->line(__("notifications.{$key}.line", [
                'value' => $this->verdict->value->label(),
                'quote' => Str::limit($this->verdict->card->quote, 120),
            ]));

        if ($this->verdict->rejection_reason !== null) {
            $message->line(__('notifications.reason', ['reason' => $this->verdict->rejection_reason]));
        }

        return $message;
    }
}
