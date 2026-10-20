<?php

namespace App\Enums;

enum VerdictValue: string
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
