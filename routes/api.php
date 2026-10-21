<?php

use App\Http\Controllers\AltchaChallengeController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\CardDecisionController;
use App\Http\Controllers\Api\V1\CardReportController;
use App\Http\Controllers\Api\V1\CardVerdictController;
use App\Http\Controllers\Api\V1\DeadlineController;
use App\Http\Controllers\Api\V1\ModerationQueueController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\PublicFigureController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\V1\VerdictController;
use App\Http\Controllers\Api\V1\VerdictDecisionController;
use Illuminate\Support\Facades\Route;

/*
 * API v1 : contrat stable pour les clients externes. Documentation : /docs/api.
 * Toute règle métier vit dans app/Actions et app/Policies ; les contrôleurs ne font que traduire.
 */
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::pattern('card', '[0-9a-fA-F-]{36}');
    Route::pattern('verdict', '[0-9a-fA-F-]{36}');

    // Lectures publiques
    Route::get('cards', [CardController::class, 'index'])->name('cards.index');
    Route::get('cards/{card}', [CardController::class, 'show'])->name('cards.show');
    Route::get('verdicts/{verdict}', [VerdictController::class, 'show'])->name('verdicts.show');
    Route::get('public-figures', [PublicFigureController::class, 'index'])->name('public-figures.index');
    Route::get('public-figures/{publicFigure:slug}', [PublicFigureController::class, 'show'])->name('public-figures.show');
    Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::get('deadlines', DeadlineController::class)->name('deadlines');
    Route::get('altcha', AltchaChallengeController::class)->name('altcha');

    // Ouvert aux visiteurs (adresse e-mail obligatoire), limité par adresse IP
    Route::middleware('throttle:proposals')->group(function () {
        Route::post('cards', [CardController::class, 'store'])->name('cards.store');
        Route::post('cards/{card}/reports', [CardReportController::class, 'store'])->name('cards.reports.store');
    });

    // Comptes
    Route::middleware('throttle:auth')->group(function () {
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('sessions', [SessionController::class, 'store'])->name('sessions.store');
        Route::post('password/forgot', [PasswordController::class, 'forgot'])->name('password.forgot');
        Route::post('password/reset', [PasswordController::class, 'reset'])->name('password.reset');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('accounts/me', [AccountController::class, 'show'])->name('accounts.me');
        Route::patch('accounts/me', [AccountController::class, 'update'])->name('accounts.update');
        Route::put('accounts/me/password', [AccountController::class, 'updatePassword'])->middleware('throttle:auth')->name('accounts.password');
        Route::delete('sessions/current', [SessionController::class, 'destroy'])->name('sessions.destroy');

        // Membres
        Route::post('cards/{card}/verdicts', [CardVerdictController::class, 'store'])->name('cards.verdicts.store');

        // Modérateurs (les actions vérifient les Policies)
        Route::patch('cards/{card}', [CardController::class, 'update'])->name('cards.update');
        Route::post('cards/{card}/decision', CardDecisionController::class)->name('cards.decision');
        Route::patch('verdicts/{verdict}', [VerdictController::class, 'update'])->name('verdicts.update');
        Route::post('verdicts/{verdict}/decision', VerdictDecisionController::class)->name('verdicts.decision');
        Route::post('public-figures', [PublicFigureController::class, 'store'])->name('public-figures.store');
        Route::patch('public-figures/{publicFigure:slug}', [PublicFigureController::class, 'update'])->name('public-figures.update');
        Route::get('moderation/queue', ModerationQueueController::class)->name('moderation.queue');
    });
});
