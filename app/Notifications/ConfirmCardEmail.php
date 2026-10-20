<?php

namespace App\Notifications;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Lien signé envoyé au visiteur qui propose une carte sans compte (règle 1).
 */
class ConfirmCardEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /** Au-delà, la carte non confirmée est purgée (cards:purge-unconfirmed). */
    public const VALID_DAYS = 7;

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
        return (new MailMessage)
            ->subject(__('notifications.confirm_card_email.subject'))
            ->line(__('notifications.confirm_card_email.line', ['quote' => Str::limit($this->card->quote, 120)]))
            ->action(__('notifications.confirm_card_email.action'), $this->confirmationUrl())
            ->line(__('notifications.confirm_card_email.expiry', ['days' => self::VALID_DAYS]))
            ->line(__('notifications.confirm_card_email.privacy'));
    }

    public function confirmationUrl(): string
    {
        return URL::temporarySignedRoute(
            'cards.confirm-email',
            now()->addDays(self::VALID_DAYS),
            ['card' => $this->card->getKey()],
        );
    }
}
