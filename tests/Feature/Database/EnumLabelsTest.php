<?php

use App\Enums\ActivityAction;
use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\SourceKind;
use App\Enums\VerdictStatus;
use App\Enums\VerdictValue;

it('a un libellé français pour chaque valeur d’enum', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->not->toStartWith('enums.');
    }
})->with([
    ActivityAction::class, CardStatus::class, CardType::class, DeadlineNature::class,
    ReportStatus::class, Role::class, SourceKind::class,
    VerdictStatus::class, VerdictValue::class,
]);

it('traduit les verdicts comme dans les maquettes', function () {
    expect(VerdictValue::PartiallyKept->label())->toBe('Partiellement tenue')
        ->and(CardStatus::Closed->label())->toBe('Tranchée');
});
