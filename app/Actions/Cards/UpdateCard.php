<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\Card;
use App\Models\User;
use App\Notifications\CardEdited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Modification de la rédaction par un modérateur (règle 2).
 */
class UpdateCard
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array<string, mixed>  $data  champs « fillable », plus public_figure_id et themes
     */
    public function handle(Card $card, User $actor, array $data): Card
    {
        Gate::forUser($actor)->authorize('update', $card);

        return DB::transaction(function () use ($card, $actor, $data) {
            $before = $this->snapshot($card);

            $card->fill($data);
            if (isset($data['public_figure_id'])) {
                $card->public_figure_id = $data['public_figure_id'];
            }
            if (array_key_exists('themes', $data)) {
                $card->themes()->sync($data['themes']);
                $card->unsetRelation('themes');
            }

            [$changedBefore, $changedAfter] = RecordActivity::diff($before, $this->snapshot($card));

            if ($changedAfter === []) {
                return $card;
            }

            $card->version++;
            $card->save();

            $this->recordActivity->handle($card, $actor, ActivityAction::Edited, $changedBefore, $changedAfter);

            if ($card->status->isPublic()) {
                $card->notifyAuthor(new CardEdited($card));
            }

            return $card;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Card $card): array
    {
        return [
            'public_figure_id' => $card->public_figure_id,
            'type' => $card->type?->value,
            'quote' => $card->quote,
            'context' => $card->context,
            'stated_on' => $card->stated_on?->toDateString(),
            'testable_statement' => $card->testable_statement,
            'verdict_criterion' => $card->verdict_criterion,
            'deadline' => $card->deadline?->toDateString(),
            'deadline_nature' => $card->deadline_nature?->value,
            'themes' => $card->themes()->orderBy('name')->pluck('name')->all(),
        ];
    }
}
