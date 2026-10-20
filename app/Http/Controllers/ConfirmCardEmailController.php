<?php

namespace App\Http\Controllers;

use App\Actions\Cards\ConfirmCardEmail;
use App\Models\Card;
use Illuminate\Contracts\View\View;

class ConfirmCardEmailController extends Controller
{
    public function __invoke(Card $card, ConfirmCardEmail $confirmCardEmail): View
    {
        $confirmCardEmail->handle($card);

        return view('cards.email-confirmed', ['card' => $card]);
    }
}
