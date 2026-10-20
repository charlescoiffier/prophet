<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VerdictValue: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    // Promesse
    case Kept = 'kept';
    case PartiallyKept = 'partially_kept';
    case Compromised = 'compromised';
    case Abandoned = 'abandoned';
    case Moot = 'moot';

    // Prédiction
    case Correct = 'correct';
    case PartiallyCorrect = 'partially_correct';
    case Wrong = 'wrong';
    case Unverifiable = 'unverifiable';

    /**
     * Couleur du rond d'état, comme dans les maquettes.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Kept, self::Correct => 'primary',
            self::PartiallyKept, self::PartiallyCorrect, self::Compromised => 'warning',
            self::Abandoned, self::Wrong => 'danger',
            self::Moot, self::Unverifiable => 'gray',
        };
    }

    /**
     * Tonalité du rond d'état sur le site public : bleu, ambre, orange ou ardoise
     * (voir les couleurs « positive », « partial »… de resources/css/app.css).
     */
    public function tone(): string
    {
        return match ($this) {
            self::Kept, self::Correct => 'positive',
            self::PartiallyKept, self::PartiallyCorrect, self::Compromised => 'partial',
            self::Abandoned, self::Wrong => 'negative',
            self::Moot, self::Unverifiable => 'neutral',
        };
    }

    public function cardType(): CardType
    {
        return match ($this) {
            self::Kept, self::PartiallyKept, self::Compromised, self::Abandoned, self::Moot => CardType::Promise,
            self::Correct, self::PartiallyCorrect, self::Wrong, self::Unverifiable => CardType::Prediction,
        };
    }

    public function appliesTo(CardType $type): bool
    {
        return $this->cardType() === $type;
    }

    /**
     * Valeurs d'une même tonalité, toutes cartes confondues (« positive » : tenue et juste).
     *
     * @return list<self>
     */
    public static function withTone(string $tone): array
    {
        return array_values(array_filter(self::cases(), fn (self $value) => $value->tone() === $tone));
    }

    /**
     * @return list<self>
     */
    public static function forType(CardType $type): array
    {
        return array_values(array_filter(self::cases(), fn (self $value) => $value->appliesTo($type)));
    }
}
