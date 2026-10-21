<?php

namespace App\Livewire\Cards;

use App\Models\Card;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fiche d'une carte. Une carte non publique n'est visible que de son auteur et des modérateurs : 404 pour les autres.
 *
 * Deux présentations, le temps de choisir : la page éditoriale (par défaut) et l'ancienne fenêtre
 * sur fond gris (docs/maquettes/Carte.html), avec ?version=fenetre.
 */
class ShowCard extends Component
{
    public Card $card;

    #[Url(except: '')]
    public string $version = '';

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

        $window = $this->version === 'fenetre';

        return view($window ? 'livewire.cards.show-card' : 'livewire.cards.show-card-page', [
            'affiliation' => $this->card->publicFigure->affiliationOn($this->card->stated_on),
        ])
            ->layout($window ? 'layouts::dialog' : 'layouts::app')
            ->title($this->card->publicFigure->name.' : '.Str::limit(trim($this->card->quote, '«» '), 70))
            ->layoutData([
                'description' => Str::limit($this->card->quote, 160).' — '.$this->card->publicFigure->name.', '.$this->card->stated_on->translatedFormat('j F Y'),
                'noindex' => ! $this->card->status->isPublic(),
            ]);
    }
}
