<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Icône UIcons (Flaticon, Regular Rounded) extraite en SVG par scripts/extract-uicons.py.
 * Elle prend la couleur du texte (currentColor) ; la taille se règle en pixels.
 */
class Uicon extends Component
{
    /** @var array<string, string> contenu SVG par nom, lu une fois par requête */
    private static array $cache = [];

    public function __construct(public string $name, public int $size = 18) {}

    public function svg(): string
    {
        $path = resource_path("icons/{$this->name}.svg");

        if (! isset(self::$cache[$this->name])) {
            if (! is_file($path)) {
                throw new InvalidArgumentException("Icône inconnue : {$this->name} (voir scripts/extract-uicons.py).");
            }
            self::$cache[$this->name] = trim(file_get_contents($path));
        }

        return self::$cache[$this->name];
    }

    public function render(): View
    {
        return view('components.uicon');
    }
}
