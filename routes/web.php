<?php

use App\Http\Controllers\ConfirmCardEmailController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Lien envoyé par e-mail au visiteur qui propose une carte sans compte (règle 1).
Route::get('/confirmations/{card}', ConfirmCardEmailController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('cards.confirm-email');

// Lien de confirmation de l'adresse d'un compte (nom attendu par la notification VerifyEmail de Laravel).
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('verification.verify');
