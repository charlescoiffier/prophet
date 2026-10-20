<?php

namespace App\Actions\Cards;

use App\Actions\RecordActivity;
use App\Actions\Sources\AttachSources;
use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\ConfirmCardEmail as ConfirmCardEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Proposition publique d'une carte, par un membre ou par un visiteur.
 *
 * Un visiteur laisse son adresse : la carte reste en attente (awaiting_email)
 * jusqu'à ce qu'il clique sur le lien de confirmation (règle 1).
 */
class ProposeCard
{
    public function __construct(
        private RecordActivity $recordActivity,
        private AttachSources $attachSources,
    ) {}

    /**
     * @param  array{public_figure_id: string, type: mixed, quote: string, stated_on: mixed, themes?: list<string>, sources?: list<array<string, mixed>>, email?: string}&array<string, mixed>  $data
     */
    public function handle(?User $actor, array $data): Card
    {
        Gate::forUser($actor)->authorize('propose', Card::class);

        $email = $actor === null ? trim((string) ($data['email'] ?? '')) : null;

        if ($actor === null && $email === '') {
            throw ValidationException::withMessages(['email' => __('validation.custom.email.required_for_guest')]);
        }

        if (empty($data['sources'])) {
            throw ValidationException::withMessages(['sources' => __('validation.custom.sources.required')]);
        }

        $card = DB::transaction(function () use ($actor, $email, $data) {
            $card = new Card($data);
            $card->public_figure_id = $data['public_figure_id'];

            if ($actor !== null) {
                $card->author()->associate($actor);
                $card->status = CardStatus::InModeration;
                $card->email_confirmed_at = now();
            } else {
                $card->author_email = $email;
                $card->status = CardStatus::AwaitingEmail;
            }

            $card->save();
            $card->themes()->sync($data['themes'] ?? []);
            $this->attachSources->handle($card, $data['sources']);

            $this->recordActivity->handle($card, $actor, ActivityAction::Proposed);

            return $card;
        });

        if ($actor === null) {
            Notification::route('mail', $email)->notify(new ConfirmCardEmailNotification($card));
        }

        return $card;
    }
}
