<?php

namespace App\Livewire\Directory;

use App\Models\PublicFigure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Toutes les personnalités qui ont au moins une carte publique, par ordre alphabétique.
 */
#[Title('Toutes les personnalités')]
class FigureIndex extends Component
{
    public function render(): View
    {
        $visible = ['cards' => fn ($query) => $query->visible()];

        return view('livewire.directory.figure-index', [
            'figures' => PublicFigure::whereHas('cards', fn ($query) => $query->visible())
                ->withCount($visible)
                ->with(['category', 'affiliations'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
