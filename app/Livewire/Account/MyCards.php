<?php

namespace App\Livewire\Account;

use App\Models\Card;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Mes propositions : cartes et verdicts, avec leur statut.
 */
#[Title('Mes propositions')]
class MyCards extends Component
{
    public function resendVerification(): void
    {
        auth()->user()->sendEmailVerificationNotification();

        session()->flash('status', 'Un nouveau lien de confirmation vient de vous être envoyé.');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.account.my-cards', [
            'cards' => Card::where('author_id', $user->getKey())
                ->with(['publicFigure', 'themes', 'approvedVerdict'])
                ->latest()
                ->get(),
            'verdicts' => $user->proposedVerdicts()->with('card.publicFigure')->latest()->get(),
        ]);
    }
}
