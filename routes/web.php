<?php

use App\Http\Controllers\ConfirmCardEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Lien envoyé par e-mail au visiteur qui propose une carte sans compte (règle 1).
Route::get('/confirmations/{card}', ConfirmCardEmailController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('cards.confirm-email');
