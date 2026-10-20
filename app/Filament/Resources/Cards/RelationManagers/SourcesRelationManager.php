<?php

namespace App\Filament\Resources\Cards\RelationManagers;

use App\Actions\Cards\AddSource;
use App\Actions\Cards\RemoveSource;
use App\Filament\Resources\Cards\Schemas\CardForm;
use App\Filament\Support\DomainAction;
use App\Models\Card;
use App\Models\Source;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'sources';

    protected static ?string $title = 'Sources de la citation';

    protected static ?string $modelLabel = 'source';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(CardForm::sourceFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                TextColumn::make('outlet')
                    ->label('Média')
                    ->placeholder('—')
                    ->description(fn (Source $record) => $record->kind->label()),
                TextColumn::make('url')
                    ->label('Lien')
                    ->limit(60)
                    ->url(fn (Source $record) => $record->url, shouldOpenInNewTab: true),
                TextColumn::make('published_on')
                    ->label('Publiée le')
                    ->date('j M Y')
                    ->placeholder('—'),
                TextColumn::make('timecode')
                    ->label('Horodatage')
                    ->placeholder('—'),
                TextColumn::make('archive_url')
                    ->label('Copie archivée')
                    ->formatStateUsing(fn (Source $record) => 'Archivée le '.$record->archived_at?->translatedFormat('j M Y'))
                    ->url(fn (Source $record) => $record->archive_url, shouldOpenInNewTab: true)
                    ->placeholder('En attente'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une source')
                    ->authorize(fn () => auth()->user()->can('update', $this->getOwnerRecord()))
                    ->using(fn (array $data, AddSource $addSource): Model => DomainAction::inForm(
                        fn () => $addSource->handle($this->ownerCard(), auth()->user(), $data)
                    )),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('Retirer')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->action(fn (Action $action, Source $record, RemoveSource $removeSource) => DomainAction::run(
                        $action,
                        fn () => $removeSource->handle($record, auth()->user()),
                        'Source retirée',
                    )),
            ]);
    }

    private function ownerCard(): Card
    {
        /** @var Card */
        return $this->getOwnerRecord();
    }
}
