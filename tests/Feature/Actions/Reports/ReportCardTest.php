<?php

use App\Actions\Reports\ReportCard;
use App\Enums\ActivityAction;
use App\Enums\ReportStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('enregistre le signalement d’un visiteur sans publier son texte', function () {
    $card = Card::factory()->published()->create();

    $report = app(ReportCard::class)->handle($card, null, 'La date est fausse.', 'lecteur@example.test');

    expect($report->refresh())
        ->status->toBe(ReportStatus::Open)
        ->email->toBe('lecteur@example.test')
        ->and($card->activityLogs()->sole())
        ->action->toBe(ActivityAction::Reported)
        ->comment->toBeNull()
        ->after->toBeNull();
});

it('rattache le signalement d’un membre à son compte', function () {
    $member = User::factory()->create();

    $report = app(ReportCard::class)->handle(Card::factory()->published()->create(), $member, 'Lien mort.');

    expect($report)->user_id->toBe($member->id)->email->toBeNull();
});

it('exige un motif et, pour un visiteur, une adresse', function () {
    $card = Card::factory()->published()->create();

    expect(fn () => app(ReportCard::class)->handle($card, null, ' ', 'a@example.test'))
        ->toThrow(ValidationException::class, 'Décrivez l’erreur')
        ->and(fn () => app(ReportCard::class)->handle($card, null, 'Erreur.'))
        ->toThrow(ValidationException::class, 'Indiquez votre adresse e-mail');
});

it('refuse de signaler une carte qui n’est pas publique', function () {
    expect(fn () => app(ReportCard::class)->handle(Card::factory()->create(), null, 'Erreur.', 'a@example.test'))
        ->toThrow(AuthorizationException::class);
});
