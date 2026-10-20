<?php

use App\Enums\CardStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\VerdictStatus;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Verdicts\Pages\ListVerdicts;
use App\Models\Report;
use App\Models\User;
use App\Models\Verdict;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

it('valide un verdict depuis la liste et tranche la carte', function () {
    $verdict = Verdict::factory()->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ListVerdicts::class)
        ->callAction(TestAction::make('approve')->table($verdict))
        ->assertNotified('Verdict validé, carte tranchée');

    expect($verdict->refresh()->status)->toBe(VerdictStatus::Approved)
        ->and($verdict->card->status)->toBe(CardStatus::Closed);
});

it('masque Valider sur son propre verdict', function () {
    $moderator = User::factory()->moderator()->create();
    $verdict = Verdict::factory()->create(['proposed_by' => $moderator->id]);

    Livewire::actingAs($moderator)
        ->test(ListVerdicts::class)
        ->assertActionHidden(TestAction::make('approve')->table($verdict));
});

it('traite un signalement', function () {
    $report = Report::factory()->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ListReports::class)
        ->callAction(TestAction::make('resolve')->table($report), ['comment' => 'Lien remplacé.']);

    expect($report->refresh()->status)->toBe(ReportStatus::Resolved);
});

it('laisse l’administrateur nommer un modérateur', function () {
    $member = User::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageUsers::class)
        ->callAction(TestAction::make('changeRole')->table($member), ['role' => 'moderator']);

    expect($member->refresh()->role)->toBe(Role::Moderator);
});
