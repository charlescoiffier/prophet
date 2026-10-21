<?php

namespace App\Livewire\Directory;

use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tous les thèmes qui ont au moins une carte publique, par ordre alphabétique.
 */
#[Title('Tous les thèmes')]
class ThemeIndex extends Component
{
    public function render(): View
    {
        return view('livewire.directory.theme-index', [
            'themes' => Theme::whereHas('cards', fn ($query) => $query->visible())
                ->withCount(['cards' => fn ($query) => $query->visible()])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
