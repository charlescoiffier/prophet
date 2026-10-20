<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportStatus;
use App\Filament\Actions\HandleReportAction;
use App\Filament\Resources\Cards\CardResource;
use App\Models\Report;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['card.publicFigure', 'user', 'handler']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reason')
                    ->label('Signalement')
                    ->wrap(),
                TextColumn::make('card.quote')
                    ->label('Carte')
                    ->limit(60)
                    ->description(fn (Report $record) => $record->card->publicFigure->name)
                    ->url(fn (Report $record) => CardResource::getUrl('view', ['record' => $record->card])),
                TextColumn::make('reporter')
                    ->label('Par')
                    ->state(fn (Report $record) => $record->user?->username ?? 'Visiteur'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Reçu')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ReportStatus::class)
                    ->default(ReportStatus::Open->value),
            ])
            ->recordActions([
                HandleReportAction::resolve(),
                HandleReportAction::dismiss(),
            ]);
    }
}
