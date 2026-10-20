<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Un auteur sans compte reçoit en plus une invitation à s'inscrire (règle 2).
 */
trait InvitesGuestToRegister
{
    protected function withInvitation(MailMessage $message, object $notifiable): MailMessage
    {
        if ($notifiable instanceof User) {
            return $message;
        }

        return $message
            ->line(__('notifications.invitation.line'))
            ->action(__('notifications.invitation.action'), url('/'));
    }
}
