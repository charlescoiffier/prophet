<?php

use App\Actions\Users\AttachGuestCards;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

it('rattache les cartes confirmées de la même adresse quand le compte confirme la sienne (règle 8)', function () {
    $user = User::factory()->create(['email' => 'visiteur@example.test']);
    $confirmed = Card::factory()->anonymous('Visiteur@example.test')->create([
        'status' => CardStatus::Published, 'email_confirmed_at' => now(),
    ]);
    $unconfirmed = Card::factory()->anonymous('visiteur@example.test')->create();
    $someoneElse = Card::factory()->anonymous('autre@example.test')->create(['email_confirmed_at' => now()]);

    event(new Verified($user));

    expect($confirmed->refresh())->author_id->toBe($user->id)->author_email->toBeNull()
        ->and($unconfirmed->refresh()->author_id)->toBeNull()
        ->and($someoneElse->refresh()->author_id)->toBeNull();
});

it('ne rattache rien à un compte dont l’adresse n’est pas confirmée', function () {
    $user = User::factory()->unverified()->create(['email' => 'visiteur@example.test']);
    $card = Card::factory()->anonymous('visiteur@example.test')->create(['email_confirmed_at' => now()]);

    app(AttachGuestCards::class)->handle($user);

    expect($card->refresh()->author_id)->toBeNull();
});
