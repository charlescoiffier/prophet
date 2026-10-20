<?php

use App\Enums\CardType;
use App\Enums\VerdictValue;

it('sépare les valeurs de verdict des promesses et des prédictions', function () {
    expect(VerdictValue::forType(CardType::Promise))->toBe([
        VerdictValue::Kept, VerdictValue::PartiallyKept, VerdictValue::Compromised, VerdictValue::Abandoned, VerdictValue::Moot,
    ])->and(VerdictValue::forType(CardType::Prediction))->toBe([
        VerdictValue::Correct, VerdictValue::PartiallyCorrect, VerdictValue::Wrong, VerdictValue::Unverifiable,
    ]);
});

it('refuse une valeur de prédiction pour une promesse', function () {
    expect(VerdictValue::Correct->appliesTo(CardType::Promise))->toBeFalse()
        ->and(VerdictValue::Kept->appliesTo(CardType::Promise))->toBeTrue();
});
