<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Saisie d'une carte par un modérateur. Elle part en modération :
 * un autre modérateur devra la publier (règle 4).
 */
class CreateCard
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{public_figure_id: string, type: mixed, quote: string, stated_on: mixed, themes?: list<string>, sources?: list<array<string, mixed>>}&array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): Card
    {
        Gate::forUser($actor)->authorize('create', Card::class);

        if (empty($data['sources'])) {
            throw ValidationException::withMessages(['sources' => __('validation.custom.sources.required')]);
        }

        return DB::transaction(function () use ($actor, $data) {
            $card = new Card($data);
            $card->public_figure_id = $data['public_figure_id'];
            $card->author()->associate($actor);
            $card->status = CardStatus::InModeration;
            $card->email_confirmed_at = now();
            $card->save();

            $card->themes()->sync($data['themes'] ?? []);

            foreach ($data['sources'] as $source) {
                $card->sources()->create($source);
            }

            $this->recordActivity->handle($card, $actor, ActivityAction::Proposed);

            return $card;
        });
    }
}
