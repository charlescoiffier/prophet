<?php

namespace App\Filament\Actions;

use App\Actions\Verdicts\UpdateVerdict;
use App\Enums\VerdictValue;
use App\Filament\Support\DomainAction;
use App\Models\Verdict;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class EditVerdictAction
{
    public static function make(): Action
    {
        return Action::make('editVerdict')
            ->label('Modifier')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->authorize('update')
            ->modalHeading('Corriger le verdict proposé')
            ->fillForm(fn (Verdict $record) => [
                'value' => $record->value->value,
                'justification' => $record->justification,
            ])
            ->schema([
                Select::make('value')
                    ->label('Verdict')
                    ->options(fn (Verdict $record) => collect(VerdictValue::forType($record->card->type))
                        ->mapWithKeys(fn (VerdictValue $value) => [$value->value => $value->label()]))
                    ->required(),
                Textarea::make('justification')
                    ->label('Justification')
                    ->required()
                    ->rows(5),
            ])
            ->action(fn (Action $action, Verdict $record, array $data, UpdateVerdict $updateVerdict) => DomainAction::run(
                $action,
                fn () => $updateVerdict->handle($record, auth()->user(), $data),
                'Verdict corrigé',
            ));
    }
}
