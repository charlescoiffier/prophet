<?php

namespace App\Livewire\Concerns;

use App\Rules\Altcha;

/**
 * Anti-robot des formulaires ouverts aux visiteurs. À appeler après la validation des
 * autres champs : une solution ne sert qu'une fois, le widget en demande alors une nouvelle.
 */
trait VerifiesAltcha
{
    public string $altcha = '';

    protected function verifyAltcha(): void
    {
        try {
            $this->validate(['altcha' => ['required', 'string', new Altcha]]);
        } finally {
            $this->altcha = '';
            $this->dispatch('altcha-reset');
        }
    }
}
