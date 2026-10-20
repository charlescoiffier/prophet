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
            self::Kept, self::Correct => 'success',
            self::PartiallyKept, self::PartiallyCorrect, self::Compromised => 'warning',
            self::Abandoned, self::Wrong => 'danger',
            self::Moot, self::Unverifiable => 'gray',
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
     * @return list<self>
     */
    public static function forType(CardType $type): array
    {
        return array_values(array_filter(self::cases(), fn (self $value) => $value->appliesTo($type)));
    }
}
