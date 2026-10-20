<?php

namespace App\Livewire\Cards;

use App\Actions\Cards\ProposeCard;
use App\Enums\CardType;
use App\Enums\SourceKind;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Ajout rapide d'une carte (docs/maquettes/Proposer.html), ouvert par la touche Q.
 */
#[Layout('layouts::dialog')]
#[Title('Proposer une carte')]
class ProposeCardForm extends Component
{
    public string $quote = '';

    public string $context = '';

    public string $publicFigureId = '';

    public string $statedOn = '';

    public string $type = 'promise';

    public string $deadline = '';

    /** @var list<string> */
    public array $themes = [];

    public string $sourceUrl = '';

    public string $sourceKind = 'article';

    public string $timecode = '';

    public string $email = '';

    public bool $charter = false;

    public ?Card $sent = null;

    public function mount(): void
    {
        $this->publicFigureId = (string) request()->query('personnalite', '');
    }

    public function submit(ProposeCard $proposeCard): void
    {
        $this->validate([
            'quote' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'string', 'max:5000'],
            'publicFigureId' => ['required', 'uuid', Rule::exists('public_figures', 'id')],
            'statedOn' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::enum(CardType::class)],
            'deadline' => ['nullable', 'date'],
            'themes' => ['array', 'max:5'],
            'themes.*' => ['uuid', Rule::exists('themes', 'id')],
            'sourceUrl' => ['required', 'url:http,https', 'max:2000'],
            'sourceKind' => ['required', Rule::enum(SourceKind::class)],
            'timecode' => ['nullable', 'regex:/^\d{1,2}(:\d{2}){1,2}$/'],
            'email' => [auth()->check() ? 'nullable' : 'required', 'email:rfc', 'max:255'],
            'charter' => ['accepted'],
        ], attributes: [
            'publicFigureId' => 'personnalité',
            'statedOn' => 'date de la déclaration',
            'sourceUrl' => 'lien vers la source',
            'sourceKind' => 'nature de la source',
            'charter' => 'charte du contributeur',
        ]);

        // Même limite que l'API : 10 propositions ou signalements par heure et par adresse IP.
        $key = 'proposals:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('quote', __('public.too_many_attempts'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $this->sent = $proposeCard->handle(auth()->user(), [
            'public_figure_id' => $this->publicFigureId,
            'type' => $this->type,
            'quote' => $this->quote,
            'context' => $this->context ?: null,
            'stated_on' => $this->statedOn,
            'deadline' => $this->deadline ?: null,
            'deadline_nature' => $this->deadline ? 'explicit' : null,
            'themes' => $this->themes,
            'sources' => [[
                'url' => $this->sourceUrl,
                'kind' => $this->sourceKind,
                'timecode' => $this->timecode ?: null,
            ]],
            'email' => auth()->check() ? null : $this->email,
        ]);
    }

    public function render(): View
    {
        return view('livewire.cards.propose-card-form', [
            'figures' => PublicFigure::orderBy('name')->get(['id', 'name']),
            'allThemes' => Theme::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
