<?php

namespace App\Filament\Resources\Verdicts\Tables;

use App\Filament\Actions\ApproveVerdictAction;
use App\Filament\Actions\EditVerdictAction;
use App\Filament\Actions\RejectVerdictAction;
use App\Models\Verdict;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VerdictsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['card.publicFigure', 'proposer'])
                ->withCount('sources'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('card.quote')
                    ->label('Carte')
                    ->limit(80)
                    ->wrap()
                    ->description(fn (Verdict $record) => $record->card->publicFigure->name),
                TextColumn::make('value')
                    ->label('Verdict')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('proposer.username')
                    ->label('Proposé par')
                    ->description(fn (Verdict $record) => $record->proposed_by === auth()->id() ? 'Votre proposition' : null),
                TextColumn::make('sources_count')
                    ->label('Sources'),
                TextColumn::make('created_at')
                    ->label('Proposé')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                ApproveVerdictAction::make(),
                RejectVerdictAction::make(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditVerdictAction::make(),
                ]),
            ]);
    }
}
