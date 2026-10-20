<?php

namespace App\Livewire\Cards;

use App\Models\Card;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Fiche d'une carte (docs/maquettes/Carte.html). Une carte non publique n'est visible
 * que de son auteur et des modérateurs : 404 pour les autres.
 */
#[Layout('layouts::dialog')]
class ShowCard extends Component
{
    public Card $card;

    public function mount(Card $card): void
    {
        abort_unless(Gate::allows('view', $card), 404);

        $this->card = $card;
    }

    #[On('verdict-proposed')]
    public function refreshCard(): void
    {
        $this->card->refresh();
    }

    public function render(): View
    {
        $this->card->load([
            'publicFigure.affiliations',
            'themes',
            'sources',
            'approvedVerdict.sources',
            'activityLogs.actor',
        ]);

        return view('livewire.cards.show-card', [
            'affiliation' => $this->card->publicFigure->affiliationOn($this->card->stated_on),
        ])
            ->title($this->card->publicFigure->name.' : '.Str::limit(trim($this->card->quote, '«» '), 70))
            ->layoutData([
                'description' => Str::limit($this->card->quote, 160).' — '.$this->card->publicFigure->name.', '.$this->card->stated_on->translatedFormat('j F Y'),
                'noindex' => ! $this->card->status->isPublic(),
            ]);
    }
}
