<?php

namespace App\Filament\Actions;

use App\Actions\Verdicts\RejectVerdict;
use App\Filament\Support\DomainAction;
use App\Models\Verdict;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class RejectVerdictAction
{
    public static function make(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('reject')
            ->modalHeading('Refuser le verdict')
            ->modalDescription('La carte attendra de nouveau un verdict. Le motif est envoyé au membre qui l’a proposé.')
            ->schema([
                Textarea::make('reason')
                    ->label('Motif du refus')
                    ->required()
                    ->rows(3),
            ])
            ->action(fn (Action $action, Verdict $record, array $data, RejectVerdict $rejectVerdict) => DomainAction::run(
                $action,
                fn () => $rejectVerdict->handle($record, auth()->user(), $data['reason']),
                'Verdict refusé',
            ));
    }
}
