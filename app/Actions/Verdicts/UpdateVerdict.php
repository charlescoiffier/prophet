<?php

namespace App\Actions\Verdicts;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\VerdictValue;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Le modérateur corrige un verdict proposé avant de le valider.
 */
class UpdateVerdict
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{value?: VerdictValue|string, justification?: string}  $data
     */
    public function handle(Verdict $verdict, User $actor, array $data): Verdict
    {
        Gate::forUser($actor)->authorize('update', $verdict);

        $before = ['value' => $verdict->value->value, 'justification' => $verdict->justification];
        $verdict->fill($data);

        if (! $verdict->value->appliesTo($verdict->card->type)) {
            throw ValidationException::withMessages(['value' => __('validation.custom.value.type')]);
        }

        [$changedBefore, $changedAfter] = RecordActivity::diff($before, [
            'value' => $verdict->value->value,
            'justification' => $verdict->justification,
        ]);

        if ($changedAfter !== []) {
            $verdict->save();
            $this->recordActivity->handle($verdict, $actor, ActivityAction::Edited, $changedBefore, $changedAfter);
        }

        return $verdict;
    }
}
