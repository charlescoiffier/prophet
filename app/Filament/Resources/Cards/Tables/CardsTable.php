<?php

namespace App\Filament\Resources\Cards\Tables;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Filament\Actions\PublishCardAction;
use App\Filament\Actions\RejectCardAction;
use App\Models\Card;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['publicFigure', 'author', 'approvedVerdict']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('quote')
                    ->label('Citation')
                    ->grow()
                    ->limit(90)
                    ->wrap()
                    ->searchable()
                    // Comme dans la maquette de modération : ses propres propositions sont signalées.
                    ->description(fn (Card $record) => $record->isAuthoredBy(auth()->user())
                        ? (auth()->user()->isAdmin()
                            ? 'Votre proposition : sa publication sera signalée dans l’historique.'
                            : 'Votre proposition : un autre modérateur doit la traiter.')
                        : null),
                TextColumn::make('publicFigure.name')
                    ->label('Personnalité')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('approvedVerdict.value')
                    ->label('Verdict')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('deadline')
                    ->label('Échéance')
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('author.username')
                    ->label('Proposée par')
                    ->placeholder('Visiteur non inscrit')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Reçue le')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(CardType::class),
                SelectFilter::make('public_figure_id')
                    ->label('Personnalité')
                    ->relationship('publicFigure', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('themes')
                    ->label('Thème')
                    ->relationship('themes', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(CardStatus::class),
            ])
            ->recordActions([
                PublishCardAction::make(),
                RejectCardAction::make(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ]);
    }
}
