<?php

use App\Filament\Resources\Cards\CardResource;
use App\Filament\Resources\PublicFigures\PublicFigureResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Themes\ThemeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Verdicts\VerdictResource;
use App\Models\Card;
use App\Models\User;
use App\Models\Verdict;
use Database\Seeders\DatabaseSeeder;

it('redirige un visiteur vers la connexion', function () {
    $this->get('/moderation')->assertRedirect('/moderation/login');
});

it('refuse le panneau à un membre', function () {
    $this->actingAs(User::factory()->create())->get('/moderation')->assertForbidden();
});

it('affiche chaque page du panneau à un modérateur, sur le jeu de démonstration', function (Closure $url) {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::where('username', 's.leroy')->sole())->get($url())->assertOk();
})->with([
    'tableau de bord' => fn () => '/moderation',
    'cartes' => fn () => CardResource::getUrl('index'),
    'fiche carte' => fn () => CardResource::getUrl('view', ['record' => Card::where('status', 'closed')->first()]),
    'saisie carte' => fn () => CardResource::getUrl('create'),
    'édition carte' => fn () => CardResource::getUrl('edit', ['record' => Card::where('status', 'published')->first()]),
    'verdicts' => fn () => VerdictResource::getUrl('index'),
    'fiche verdict' => fn () => VerdictResource::getUrl('view', ['record' => Verdict::first()]),
    'signalements' => fn () => ReportResource::getUrl('index'),
    'personnalités' => fn () => PublicFigureResource::getUrl('index'),
    'thèmes' => fn () => ThemeResource::getUrl('index'),
]);

it('réserve la gestion des comptes à l’administrateur', function () {
    $this->actingAs(User::factory()->moderator()->create())->get(UserResource::getUrl('index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(UserResource::getUrl('index'))->assertOk();
});
