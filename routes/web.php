<?php

use App\Http\Controllers\ConfirmCardEmailController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\VerifyEmailController;
use App\Livewire\Account\MyCards;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPasswordForm;
use App\Livewire\Board;
use App\Livewire\Cards\ProposeCardForm;
use App\Livewire\Cards\ShowCard;
use Illuminate\Support\Facades\Route;

/*
 * Site public (Blade + Livewire, rendu serveur). Les règles métier sont dans app/Actions.
 */

// Vues de la barre latérale
Route::get('/', Board::class)->name('home');
Route::get('/verdict-attendu', Board::class)->defaults('view', 'awaiting')->name('cards.awaiting');
Route::get('/echeances', Board::class)->defaults('view', 'upcoming')->name('cards.upcoming');
Route::get('/tranchees', Board::class)->defaults('view', 'closed')->name('cards.closed');
Route::get('/recherche', Board::class)->defaults('view', 'search')->name('search');
Route::get('/personnalites/{figure:slug}', Board::class)->name('figures.show');
Route::get('/themes/{theme:slug}', Board::class)->name('themes.show');

// Cartes
Route::get('/proposer', ProposeCardForm::class)->name('cards.propose');
Route::get('/cartes/{card}', ShowCard::class)->whereUuid('card')->name('cards.show');

// Lien envoyé par e-mail au visiteur qui propose une carte sans compte (règle 1).
Route::get('/confirmations/{card}', ConfirmCardEmailController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('cards.confirm-email');

// Comptes
Route::middleware('guest')->group(function () {
    Route::get('/connexion', Login::class)->name('login');
    Route::get('/inscription', Register::class)->name('register');
    Route::get('/mot-de-passe/oublie', ForgotPassword::class)->name('password.request');
    Route::get('/mot-de-passe/nouveau', ResetPasswordForm::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/mes-propositions', MyCards::class)->name('account.cards');
    Route::post('/deconnexion', LogoutController::class)->name('logout');
});

// Lien de confirmation de l'adresse d'un compte (nom attendu par la notification VerifyEmail de Laravel).
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('verification.verify');

// Pages de texte
Route::view('/charte', 'pages.charter')->name('pages.charter');
Route::view('/donnees-personnelles', 'pages.privacy')->name('pages.privacy');
