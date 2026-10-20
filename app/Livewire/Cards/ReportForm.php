<?php

namespace App\Livewire\Cards;

use App\Actions\Reports\ReportCard;
use App\Models\Card;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * « Signaler une erreur » sur la fiche d'une carte.
 */
class ReportForm extends Component
{
    public Card $card;

    public bool $open = false;

    public bool $sent = false;

    public string $reason = '';

    public string $email = '';

    public function submit(ReportCard $reportCard): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'email' => [auth()->check() ? 'nullable' : 'required', 'email:rfc', 'max:255'],
        ]);

        // Même limite que l'API : 10 propositions ou signalements par heure et par adresse IP.
        $key = 'proposals:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('reason', __('public.too_many_attempts'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $reportCard->handle($this->card, auth()->user(), $this->reason, auth()->check() ? null : $this->email);

        $this->reset('reason', 'email', 'open');
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.cards.report-form');
    }
}
