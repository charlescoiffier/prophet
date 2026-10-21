<?php

use App\Enums\CardStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\VerdictStatus;
use App\Filament\Resources\PublicFigures\Pages\CreatePublicFigure;
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

it('laisse l’administrateur modifier la fiche d’un compte et nommer un modérateur', function () {
    $member = User::factory()->create(['username' => 'lecteur', 'email' => 'lecteur@example.test']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageUsers::class)
        ->callAction(TestAction::make('editAccount')->table($member), [
            'first_name' => 'Léa',
            'last_name' => 'Martin',
            'username' => 'lecteur',
            'email' => 'lecteur@example.test',
            'role' => 'moderator',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Compte enregistré');

    expect($member->refresh())
        ->first_name->toBe('Léa')
        ->last_name->toBe('Martin')
        ->role->toBe(Role::Moderator)
        ->hasVerifiedEmail()->toBeTrue();
});

it('refuse dans la fiche d’un compte un pseudo déjà pris', function () {
    User::factory()->create(['username' => 'pris']);
    $member = User::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageUsers::class)
        ->callAction(TestAction::make('editAccount')->table($member), ['username' => 'pris'])
        ->assertHasFormErrors(['username' => 'unique']);
});

it('fait suivre l’identifiant d’URL au nom tant qu’il n’a pas été retouché', function () {
    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(CreatePublicFigure::class)
        ->fillForm(['name' => 'Élise Démo'])
        ->assertSchemaStateSet(['slug' => 'elise-demo'])
        ->fillForm(['slug' => 'elise'])
        ->fillForm(['name' => 'Élise Démonstration'])
        ->assertSchemaStateSet(['slug' => 'elise']);
});

it('laisse l’administrateur supprimer puis restaurer un compte depuis le panneau', function () {
    $member = User::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($member))
        ->assertNotified('Compte supprimé');

    expect($member->refresh()->trashed())->toBeTrue();

    Livewire::actingAs(User::where('role', 'admin')->first())
        ->test(ManageUsers::class)
        ->filterTable('trashed', true)
        ->callAction(TestAction::make('restoreAccount')->table($member));

    expect($member->refresh()->trashed())->toBeFalse();
});
