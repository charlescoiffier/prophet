<?php

namespace App\Actions\Verdicts;

use App\Actions\RecordActivity;
use App\Actions\Sources\AttachSources;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Un membre propose un verdict sourcé (règle 3). La carte passe en « verdict proposé »
 * et n'accepte plus d'autre proposition tant que celle-ci n'est pas tranchée (règle 5).
 */
class ProposeVerdict
{
    public function __construct(
        private RecordActivity $recordActivity,
        private AttachSources $attachSources,
    ) {}

    /**
     * @param  array{value: VerdictValue|string, justification: string, sources?: list<array<string, mixed>>}  $data
     */
    public function handle(Card $card, User $actor, array $data): Verdict
    {
        Gate::forUser($actor)->authorize('propose', [Verdict::class, $card]);

        $value = $data['value'] instanceof VerdictValue ? $data['value'] : VerdictValue::tryFrom((string) $data['value']);

        if ($value === null || ! $value->appliesTo($card->type)) {
            throw ValidationException::withMessages(['value' => __('validation.custom.value.type')]);
        }

        if (empty($data['sources'])) {
            throw ValidationException::withMessages(['sources' => __('validation.custom.sources.verdict_required')]);
        }

        return DB::transaction(function () use ($card, $actor, $data, $value) {
            // Verrou : deux membres qui proposent en même temps ne passent pas tous les deux.
            $card = Card::whereKey($card->getKey())->lockForUpdate()->firstOrFail();

            if ($card->status !== CardStatus::Published) {
                throw ValidationException::withMessages(['value' => __('validation.custom.value.pending')]);
            }

            $verdict = new Verdict(['value' => $value, 'justification' => $data['justification']]);
            $verdict->card()->associate($card);
            $verdict->proposer()->associate($actor);
            $verdict->status = VerdictStatus::Proposed;
            $verdict->save();

            $this->attachSources->handle($verdict, $data['sources']);

            $card->status = CardStatus::VerdictProposed;
            $card->save();

            $this->recordActivity->handle($verdict, $actor, ActivityAction::Proposed, after: ['value' => $value->value]);
            $this->recordActivity->handle(
                $card, $actor, ActivityAction::Proposed,
                ['status' => CardStatus::Published->value],
                ['status' => CardStatus::VerdictProposed->value, 'verdict' => $value->value],
            );

            return $verdict;
        });
    }
}
