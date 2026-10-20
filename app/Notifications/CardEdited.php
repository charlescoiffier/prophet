<?php

namespace App\Notifications;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CardEdited extends Notification implements ShouldQueue
{
    use InvitesGuestToRegister, Queueable;

    public function __construct(public Card $card) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->withInvitation((new MailMessage)
            ->subject(__('notifications.card_edited.subject'))
            ->line(__('notifications.card_edited.line', ['quote' => Str::limit($this->card->quote, 120)])), $notifiable);
    }
}
