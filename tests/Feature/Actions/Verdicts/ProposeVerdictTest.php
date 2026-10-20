<?php

use App\Actions\Verdicts\ProposeVerdict;
use App\Enums\CardStatus;
use App\Enums\VerdictStatus;
use App\Jobs\ArchiveSource;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Queue::fake());

function verdictInput(array $overrides = []): array
{
    return [
        'value' => 'wrong',
        'justification' => 'Le taux publié est de 7,4 %.',
        'sources' => [['url' => 'https://example.org/chiffres', 'kind' => 'public_data']],
        ...$overrides,
    ];
}

it('enregistre la proposition sourcée et passe la carte en verdict proposé (règle 3)', function () {
    $card = Card::factory()->overdue()->prediction()->create();
    $member = User::factory()->create();

    $verdict = app(ProposeVerdict::class)->handle($card, $member, verdictInput());

    expect($verdict->refresh())
        ->status->toBe(VerdictStatus::Proposed)
        ->proposed_by->toBe($member->id)
        ->and($verdict->sources()->sole()->url)->toBe('https://example.org/chiffres')
        ->and($card->refresh()->status)->toBe(CardStatus::VerdictProposed);
    Queue::assertPushed(ArchiveSource::class);
});

it('exige au moins une source', function () {
    $card = Card::factory()->overdue()->prediction()->create();

    expect(fn () => app(ProposeVerdict::class)->handle($card, User::factory()->create(), verdictInput(['sources' => []])))
        ->toThrow(ValidationException::class, 'Un verdict doit citer au moins une source.');
});

it('refuse une valeur qui ne correspond pas au type de la carte', function () {
    $card = Card::factory()->overdue()->prediction()->create();

    expect(fn () => app(ProposeVerdict::class)->handle($card, User::factory()->create(), verdictInput(['value' => 'kept'])))
        ->toThrow(ValidationException::class, 'ne correspond pas au type de la carte');
});

it('refuse une seconde proposition tant que la première est en attente (règle 5)', function () {
    $card = Card::factory()->overdue()->prediction()->create();
    app(ProposeVerdict::class)->handle($card, User::factory()->create(), verdictInput());

    expect(fn () => app(ProposeVerdict::class)->handle($card->refresh(), User::factory()->create(), verdictInput()))
        ->toThrow(AuthorizationException::class);
});

it('refuse la proposition sur une carte déjà passée en verdict proposé entre-temps', function () {
    $card = Card::factory()->overdue()->prediction()->create();
    $stale = Card::find($card->id);
    app(ProposeVerdict::class)->handle($card, User::factory()->create(), verdictInput());

    expect(fn () => app(ProposeVerdict::class)->handle($stale, User::factory()->create(), verdictInput()))
        ->toThrow(ValidationException::class, 'n’accepte pas de proposition de verdict');
});

it('refuse un compte à l’adresse non confirmée', function () {
    $card = Card::factory()->overdue()->prediction()->create();

    expect(fn () => app(ProposeVerdict::class)->handle($card, User::factory()->unverified()->create(), verdictInput()))
        ->toThrow(AuthorizationException::class);
});

it('refuse une carte qui n’est pas publiée', function (CardStatus $status) {
    $card = Card::factory()->prediction()->create(['status' => $status]);

    expect(User::factory()->create()->can('propose', [Verdict::class, $card]))->toBeFalse();
})->with([CardStatus::InModeration, CardStatus::Closed, CardStatus::Rejected]);
