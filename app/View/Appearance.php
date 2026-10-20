<?php

namespace App\View;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Models\Card;
use App\Models\PublicFigure;
use Illuminate\Support\Str;

/**
 * Apparence des cartes sur le site public, d'après la maquette éditoriale (docs/maquettes/editoriale).
 *
 * Les classes Tailwind sont écrites en entier ici pour que Tailwind les détecte.
 */
class Appearance
{
    /**
     * Tonalités de verdict : adresse (/verdicts/{slug}) => tonalité et libellé de la barre latérale.
     *
     * @var array<string, array{tone: string, label: string, mark: string, icon: string}>
     */
    public const VERDICT_TONES = [
        'tenues-ou-justes' => ['tone' => 'positive', 'label' => 'Tenues ou justes', 'mark' => '✓', 'icon' => 'verdict-positive'],
        'partielles' => ['tone' => 'partial', 'label' => 'Partielles', 'mark' => '≈', 'icon' => 'verdict-partial'],
        'abandonnees-ou-fausses' => ['tone' => 'negative', 'label' => 'Abandonnées ou fausses', 'mark' => '×', 'icon' => 'verdict-negative'],
        'caduques-ou-inverifiables' => ['tone' => 'neutral', 'label' => 'Caduques ou invérifiables', 'mark' => '?', 'icon' => 'verdict-neutral'],
    ];

    /**
     * Fond d'une carte de la grille : la couleur signifie toujours un verdict. Une carte tranchée prend
     * la couleur du sien ; une carte encore ouverte (ou en relecture) garde le fond papier.
     */
    public static function surfaceClasses(Card $card): string
    {
        return match (self::tone($card)) {
            'positive' => 'bg-card-blue',
            'partial' => 'bg-card-yellow',
            'negative' => 'bg-card-red',
            'neutral' => 'bg-card-grey',
            default => 'bg-field',
        };
    }

    /**
     * Statut en bas de carte : « ✓ Juste », « En relecture », « Verdict attendu », « À suivre ↗ ».
     */
    public static function statusLabel(Card $card): string
    {
        if ($card->status === CardStatus::Closed && $card->approvedVerdict !== null) {
            return self::markFor($card->approvedVerdict->value->tone()).' '.$card->approvedVerdict->value->label();
        }

        return match (true) {
            $card->status === CardStatus::VerdictProposed => 'En relecture',
            self::isOverdue($card) => 'Verdict attendu',
            default => 'À suivre ↗',
        };
    }

    /** Icône du type (resources/icons) : poignée de main pour une promesse, boule de cristal pour une prédiction. */
    public static function typeIcon(CardType $type): string
    {
        return $type === CardType::Promise ? 'promise' : 'prediction';
    }

    /**
     * Rond d'état (fiche d'une carte, mes propositions) : couleur du verdict,
     * rouge si l'échéance est dépassée sans verdict, gris sinon.
     */
    public static function ringClasses(Card $card): string
    {
        return match (self::tone($card)) {
            'positive' => 'border-positive bg-positive-fill',
            'partial' => 'border-partial bg-partial-fill',
            'negative' => 'border-negative bg-negative-fill',
            'neutral' => 'border-neutral bg-neutral-fill',
            'overdue' => 'border-negative',
            default => 'border-pending',
        };
    }

    public static function verdictTextClasses(Card $card): string
    {
        return match (self::tone($card)) {
            'positive' => 'text-positive-text',
            'partial' => 'text-partial-text',
            'negative' => 'text-negative-text',
            'neutral' => 'text-neutral-text',
            default => 'text-muted',
        };
    }

    /**
     * Encadré du verdict sur la fiche d'une carte : l'aplat de la carte tranchée.
     */
    public static function verdictPanelClasses(Card $card): string
    {
        return match (self::tone($card)) {
            'positive' => 'bg-card-blue',
            'partial' => 'bg-card-yellow',
            'negative' => 'bg-card-red',
            default => 'bg-card-grey',
        };
    }

    public static function isOverdue(Card $card): bool
    {
        return $card->status === CardStatus::Published
            && $card->deadline !== null
            && $card->deadline->isPast()
            && ! $card->deadline->isToday();
    }

    public static function markFor(string $tone): string
    {
        return collect(self::VERDICT_TONES)->firstWhere('tone', $tone)['mark'] ?? '';
    }

    /**
     * Monogramme d'une personnalité sans photo : « Jeanne Exemple » → « JE ».
     */
    public static function monogram(PublicFigure $figure): string
    {
        return Str::upper(collect(preg_split('/[\s\-]+/u', $figure->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->implode(''));
    }

    /**
     * Initiales pour l'avatar de l'historique : « s.leroy » → « SL ».
     */
    public static function initials(?string $username): string
    {
        if ($username === null) {
            return 'V';
        }

        return Str::upper(collect(preg_split('/[._\-\s]+/', $username))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->implode(''));
    }

    private static function tone(Card $card): string
    {
        if ($card->status === CardStatus::Closed && $card->approvedVerdict !== null) {
            return $card->approvedVerdict->value->tone();
        }

        return self::isOverdue($card) ? 'overdue' : 'pending';
    }
}
