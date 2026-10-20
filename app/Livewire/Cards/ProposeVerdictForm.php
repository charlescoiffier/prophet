<?php

namespace App\Livewire\Cards;

use App\Actions\Verdicts\ProposeVerdict;
use App\Enums\SourceKind;
use App\Enums\VerdictValue;
use App\Models\Card;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Proposition d'un verdict sourcé par un membre (règles 3 et 5).
 */
class ProposeVerdictForm extends Component
{
    public Card $card;

    public bool $open = false;

    public string $value = '';

    public string $justification = '';

    public string $sourceUrl = '';

    public string $sourceKind = 'public_data';

    public string $sourceOutlet = '';

    public function submit(ProposeVerdict $proposeVerdict): void
    {
        $this->validate([
            'value' => ['required', Rule::in(array_map(fn (VerdictValue $value) => $value->value, VerdictValue::forType($this->card->type)))],
            'justification' => ['required', 'string', 'max:5000'],
            'sourceUrl' => ['required', 'url:http,https', 'max:2000'],
            'sourceKind' => ['required', Rule::enum(SourceKind::class)],
            'sourceOutlet' => ['nullable', 'string', 'max:255'],
        ], attributes: ['sourceUrl' => 'lien de la source', 'sourceKind' => 'nature de la source', 'sourceOutlet' => 'média']);

        $proposeVerdict->handle($this->card, auth()->user(), [
            'value' => $this->value,
            'justification' => $this->justification,
            'sources' => [['url' => $this->sourceUrl, 'kind' => $this->sourceKind, 'outlet' => $this->sourceOutlet ?: null]],
        ]);

        $this->dispatch('verdict-proposed');
    }

    public function render(): View
    {
        return view('livewire.cards.propose-verdict-form', [
            'values' => VerdictValue::forType($this->card->type),
        ]);
    }
}
