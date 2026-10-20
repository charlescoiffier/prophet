<?php

namespace App\Filament\Actions;

use App\Actions\Cards\RejectCard;
use App\Filament\Support\DomainAction;
use App\Models\Card;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class RejectCardAction
{
    public static function make(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('reject')
            ->modalHeading('Refuser la carte')
            ->modalDescription('Le motif est envoyé à l’auteur et apparaît dans l’historique public.')
            ->schema([
                Textarea::make('reason')
                    ->label('Motif du refus')
                    ->required()
                    ->rows(3),
            ])
            ->action(fn (Action $action, Card $record, array $data, RejectCard $rejectCard) => DomainAction::run(
                $action,
                fn () => $rejectCard->handle($record, auth()->user(), $data['reason']),
                'Carte refusée',
            ));
    }
}
