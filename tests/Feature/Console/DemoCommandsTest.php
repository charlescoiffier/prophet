<?php

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('charge la démonstration avec des comptes impossibles à ouvrir et sans administrateur', function () {
    $this->artisan('demo:load')->assertSuccessful();

    $demoUsers = User::where('email', 'like', '%@example.test')->get();

    expect(Card::count())->toBeGreaterThan(30)
        ->and($demoUsers)->not->toBeEmpty()
        ->and($demoUsers->contains(fn (User $user) => $user->role === Role::Admin))->toBeFalse()
        ->and($demoUsers->contains(fn (User $user) => Hash::check('password', $user->password)))->toBeFalse();
});

it('refuse de charger deux fois la démonstration', function () {
    $this->artisan('demo:load')->assertSuccessful();

    $this->artisan('demo:load')->assertFailed();
});

it('efface la démonstration sans toucher aux vraies données', function () {
    $this->artisan('demo:load')->assertSuccessful();
    $admin = User::factory()->admin()->create(['email' => 'charles@exemple.fr']);
    $realCard = Card::factory()->published()->create(['author_id' => $admin->id]);
    $realCard->themes()->attach(Theme::where('slug', 'sante')->sole());

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(Card::pluck('id')->all())->toBe([$realCard->id])
        ->and(PublicFigure::pluck('id')->all())->toBe([$realCard->public_figure_id])
        ->and(User::pluck('id')->all())->toBe([$admin->id])
        ->and(Theme::where('slug', 'fiscalite')->exists())->toBeFalse()
        ->and(Theme::where('slug', 'sante')->exists())->toBeTrue()
        ->and(ActivityLog::count())->toBe(0);
});

it('demande confirmation avant d’effacer', function () {
    $this->artisan('demo:load')->assertSuccessful();

    $this->artisan('demo:purge')->expectsConfirmation('Les effacer définitivement ?', 'no')->assertFailed();

    expect(Card::count())->toBeGreaterThan(30);
});
