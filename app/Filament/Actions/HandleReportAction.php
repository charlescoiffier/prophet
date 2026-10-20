<?php

namespace App\Filament\Actions;

use App\Actions\Reports\HandleReport;
use App\Enums\ReportStatus;
use App\Filament\Support\DomainAction;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class HandleReportAction
{
    public static function resolve(): Action
    {
        return self::make(ReportStatus::Resolved, 'resolve', 'Traité', Heroicon::OutlinedCheckCircle, 'success', 'Signalement traité');
    }

    public static function dismiss(): Action
    {
        return self::make(ReportStatus::Dismissed, 'dismiss', 'Rejeter', Heroicon::OutlinedXCircle, 'gray', 'Signalement rejeté');
    }

    private static function make(ReportStatus $outcome, string $name, string $label, Heroicon $icon, string $color, string $success): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->authorize('handle')
            ->modalHeading($label.' le signalement')
            ->schema([
                Textarea::make('comment')
                    ->label('Note pour l’historique de la carte')
                    ->rows(2),
            ])
            ->action(fn (Action $action, Report $record, array $data, HandleReport $handleReport) => DomainAction::run(
                $action,
                fn () => $handleReport->handle($record, auth()->user(), $outcome, $data['comment'] ?: null),
                $success,
            ));
    }
}
