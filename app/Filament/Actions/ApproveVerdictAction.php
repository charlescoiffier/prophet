<?php

namespace App\Filament\Actions;

use App\Actions\Verdicts\ApproveVerdict;
use App\Filament\Support\DomainAction;
use App\Models\Verdict;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ApproveVerdictAction
{
    public static function make(): Action
    {
        return Action::make('approve')
            ->label('Valider')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('approve')
            ->requiresConfirmation()
            ->modalHeading('Valider le verdict')
            ->modalDescription(fn (Verdict $record) => "La carte sera tranchée : « {$record->value->label()} ».")
            ->action(fn (Action $action, Verdict $record, ApproveVerdict $approveVerdict) => DomainAction::run(
                $action,
                fn () => $approveVerdict->handle($record, auth()->user()),
                'Verdict validé, carte tranchée',
            ));
    }
}
