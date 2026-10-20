<?php

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('laisse un modérateur publier ou refuser une carte en modération proposée par un autre', function (string $ability) {
    $card = Card::factory()->create(['status' => CardStatus::InModeration]);

    expect(User::factory()->moderator()->create()->can($ability, $card))->toBeTrue()
        ->and(User::factory()->admin()->create()->can($ability, $card))->toBeTrue();
})->with(['publish', 'reject']);

it('interdit à un membre de publier ou refuser', function (string $ability) {
    $card = Card::factory()->create(['status' => CardStatus::InModeration]);

    expect(User::factory()->create()->can($ability, $card))->toBeFalse();
})->with(['publish', 'reject']);

it('interdit de décider sur sa propre carte, admin compris (règle 4)', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect($admin->can($ability, Card::factory()->create(['author_id' => $admin->id])))->toBeFalse();
})->with(['publish', 'reject', 'update']);

it('reconnaît la carte proposée sans compte avec la même adresse comme la sienne', function () {
    $moderator = User::factory()->moderator()->create(['email' => 'modo@example.test']);
    $card = Card::factory()->anonymous('modo@example.test')->create(['status' => CardStatus::InModeration]);

    expect($moderator->can('publish', $card))->toBeFalse();
});

it('ne publie que les cartes en modération', function (CardStatus $status) {
    $card = Card::factory()->create(['status' => $status]);

    expect(User::factory()->moderator()->create()->can('publish', $card))->toBeFalse();
})->with([CardStatus::AwaitingEmail, CardStatus::Published, CardStatus::Rejected, CardStatus::Closed]);

it('ne modifie pas la rédaction d’une carte refusée ou dont l’e-mail n’est pas confirmé', function (CardStatus $status) {
    $card = Card::factory()->create(['status' => $status]);

    expect(User::factory()->moderator()->create()->can('update', $card))->toBeFalse();
})->with([CardStatus::AwaitingEmail, CardStatus::Rejected]);

it('ne supprime jamais une carte', function () {
    expect(User::factory()->admin()->create()->can('delete', Card::factory()->create()))->toBeFalse();
});

it('montre au public les cartes publiées seulement', function () {
    $visitor = null;

    expect(Gate::forUser($visitor)->allows('view', Card::factory()->published()->create()))->toBeTrue()
        ->and(Gate::forUser($visitor)->allows('view', Card::factory()->create()))->toBeFalse();
});
