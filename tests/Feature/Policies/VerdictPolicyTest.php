<?php

use App\Enums\VerdictStatus;
use App\Models\User;
use App\Models\Verdict;

it('laisse un modérateur trancher le verdict proposé par un autre', function (string $ability) {
    expect(User::factory()->moderator()->create()->can($ability, Verdict::factory()->create()))->toBeTrue();
})->with(['approve', 'reject', 'update']);

it('interdit de trancher son propre verdict (règle 4)', function (string $ability) {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->can($ability, Verdict::factory()->create(['proposed_by' => $moderator->id])))->toBeFalse();
})->with(['approve', 'reject', 'update']);

it('interdit à un membre de trancher', function () {
    expect(User::factory()->create()->can('approve', Verdict::factory()->create()))->toBeFalse();
});

it('ne revient pas sur un verdict déjà tranché', function (VerdictStatus $status) {
    $verdict = Verdict::factory()->create(['status' => $status]);

    expect(User::factory()->moderator()->create()->can('approve', $verdict))->toBeFalse();
})->with([VerdictStatus::Approved, VerdictStatus::Rejected]);
