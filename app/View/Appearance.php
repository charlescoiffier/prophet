<?php

namespace App\View;

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\PublicFigure;
use Illuminate\Support\Str;

/**
 * Apparence des cartes sur le site public, d'après docs/maquettes.
 *
 * Les classes Tailwind sont écrites en entier ici pour que Tailwind les détecte.
 */
class Appearance
{
    /** Pastilles des personnalités, dans l'ordre des maquettes. */
    private const FIGURE_COLORS = ['#2552C6', '#0F766E', '#B7791F', '#7C3AED', '#C2410C', '#475569'];

    /**
     * Rond d'état : coloré et rempli selon le verdict ; cerclé d'orange si l'échéance
     * est dépassée sans verdict ; gris sinon.
     */
    public static function ringClasses(Card $card): string
    {
        $tone = self::tone($card);

        return match ($tone) {
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
     * Encadré du verdict sur la fiche d'une carte.
     */
    public static function verdictPanelClasses(Card $card): string
    {
        return match (self::tone($card)) {
            'positive' => 'bg-[#F3F6FD] border-accent-line',
            'partial' => 'bg-[#FFF9EC] border-[#F0DFB4]',
            'negative' => 'bg-[#FEF4EF] border-[#F3CDB9]',
            default => 'bg-[#F5F6F8] border-[#DDE1E6]',
        };
    }

    public static function isOverdue(Card $card): bool
    {
        return $card->status === CardStatus::Published
            && $card->deadline !== null
            && $card->deadline->isPast()
            && ! $card->deadline->isToday();
    }

    public static function figureColor(PublicFigure $figure): string
    {
        return self::FIGURE_COLORS[crc32($figure->getKey()) % count(self::FIGURE_COLORS)];
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
