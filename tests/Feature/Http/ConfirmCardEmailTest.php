<?php

use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use App\Notifications\ConfirmCardEmail;

it('confirme l’adresse par le lien signé et envoie la carte en modération', function () {
    $card = Card::factory()->anonymous('visiteur@example.test')->create();

    $this->get((new ConfirmCardEmail($card))->confirmationUrl())
        ->assertOk()
        ->assertSee('votre adresse est confirmée');

    expect($card->refresh())
        ->status->toBe(CardStatus::InModeration)
        ->email_confirmed_at->not->toBeNull()
        ->and($card->activityLogs()->sole()->action)->toBe(ActivityAction::EmailConfirmed);
});

it('refuse un lien modifié ou non signé', function () {
    $card = Card::factory()->anonymous()->create();

    $this->get(route('cards.confirm-email', $card))->assertForbidden();

    expect($card->refresh()->status)->toBe(CardStatus::AwaitingEmail);
});

it('refuse un lien expiré', function () {
    $card = Card::factory()->anonymous()->create();
    $url = (new ConfirmCardEmail($card))->confirmationUrl();

    $this->travel(ConfirmCardEmail::VALID_DAYS + 1)->days();

    $this->get($url)->assertForbidden();
});

it('ne fait rien au second clic', function () {
    $card = Card::factory()->anonymous()->create();
    $url = (new ConfirmCardEmail($card))->confirmationUrl();

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    expect($card->activityLogs()->count())->toBe(1);
});

it('rattache la carte au compte confirmé qui a la même adresse', function () {
    $user = User::factory()->create(['email' => 'membre@example.test']);
    $card = Card::factory()->anonymous('Membre@Example.test')->create();

    $this->get((new ConfirmCardEmail($card))->confirmationUrl())->assertOk();

    expect($card->refresh()->author_id)->toBe($user->id);
});
