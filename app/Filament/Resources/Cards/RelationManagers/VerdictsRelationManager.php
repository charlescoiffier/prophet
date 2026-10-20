<?php

namespace App\Filament\Resources\Cards\RelationManagers;

use App\Filament\Resources\Verdicts\VerdictResource;
use App\Models\Verdict;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Propositions de verdict successives, en lecture : on les tranche depuis « Verdicts ».
 */
class VerdictsRelationManager extends RelationManager
{
    protected static string $relationship = 'verdicts';

    protected static ?string $title = 'Propositions de verdict';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('value')->label('Verdict')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('proposer.username')->label('Proposé par'),
                TextColumn::make('created_at')->label('Le')->date('j M Y'),
                TextColumn::make('decider.username')->label('Tranché par')->placeholder('—'),
            ])
            ->recordUrl(fn (Verdict $record) => VerdictResource::getUrl('view', ['record' => $record]));
    }
}
