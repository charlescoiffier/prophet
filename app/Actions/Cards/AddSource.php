<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Actions\Sources\AttachSources;
use App\Enums\ActivityAction;
use App\Models\Card;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AddSource
{
    public function __construct(
        private RecordActivity $recordActivity,
        private AttachSources $attachSources,
    ) {}

    /**
     * @param  array{url: string, kind: mixed}&array<string, mixed>  $data
     */
    public function handle(Card $card, User $actor, array $data): Source
    {
        Gate::forUser($actor)->authorize('update', $card);

        return DB::transaction(function () use ($card, $actor, $data) {
            $source = $this->attachSources->handle($card, [$data])->sole();

            $this->recordActivity->handle($card, $actor, ActivityAction::SourceAdded, after: ['source' => $source->url]);

            return $source;
        });
    }
}
