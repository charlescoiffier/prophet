<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RemoveSource
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Source $source, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $source);

        $owner = $source->card ?? $source->verdict;

        if ($owner->sources()->count() <= 1) {
            throw ValidationException::withMessages(['source' => __('validation.custom.sources.last')]);
        }

        DB::transaction(function () use ($source, $actor, $owner) {
            $source->delete();

            $this->recordActivity->handle($owner, $actor, ActivityAction::SourceRemoved, before: ['source' => $source->url]);
        });
    }
}
