<?php

namespace App\Filament\Resources\Cards\RelationManagers;

use App\Models\ActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique de la carte (règle 6), en lecture seule : le journal n'est jamais modifié.
 */
class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Historique';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('actor'))
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('created_at')->label('Le')->dateTime('j M Y, H:i'),
                TextColumn::make('actor.username')->label('Par')->placeholder('Visiteur ou système'),
                TextColumn::make('action')->label('Action')->badge()->color('gray'),
                TextColumn::make('changes')
                    ->label('Changements')
                    ->state(fn (ActivityLog $record) => $record->describeChanges())
                    ->wrap()
                    ->lineClamp(4),
                TextColumn::make('comment')->label('Commentaire')->wrap()->placeholder('—'),
            ]);
    }
}
