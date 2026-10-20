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

it('interdit à un modérateur de décider sur sa propre carte (règle 4)', function (string $ability) {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->can($ability, Card::factory()->create(['author_id' => $moderator->id])))->toBeFalse();
})->with(['publish', 'reject', 'update']);

it('laisse l’admin publier et modifier sa propre carte, mais pas la refuser', function () {
    $admin = User::factory()->admin()->create();
    $card = Card::factory()->create(['author_id' => $admin->id]);

    expect($admin->can('publish', $card))->toBeTrue()
        ->and($admin->can('update', $card))->toBeTrue()
        ->and($admin->can('reject', $card))->toBeFalse();
});

it('ne laisse pas l’admin publier sa carte avant confirmation de l’e-mail', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);

    expect($admin->can('publish', Card::factory()->anonymous('admin@example.test')->create()))->toBeFalse();
});

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
