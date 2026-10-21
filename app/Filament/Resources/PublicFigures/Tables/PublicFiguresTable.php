<?php

namespace App\Filament\Resources\PublicFigures\Tables;

use App\Models\PublicFigure;
use App\View\Appearance;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicFiguresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'affiliations'])->withCount('cards'))
            ->defaultSort(fn (Builder $query) => $query->alphabetical())
            ->columns([
                // Petit portrait : la photo, ou le monogramme du site à défaut.
                ImageColumn::make('photo')
                    ->label('')
                    ->state(fn (PublicFigure $record): ?string => $record->photoUrl())
                    ->defaultImageUrl(fn (PublicFigure $record): string => self::monogramImage($record))
                    ->imageSize(36)
                    // Lien cassé ou fichier disparu : le monogramme prend le relais, comme sur le site.
                    ->extraImgAttributes(fn (PublicFigure $record): array => [
                        'loading' => 'lazy',
                        'class' => 'object-cover',
                        'x-data' => '',
                        'x-on:error' => "\$el.src = '".self::monogramImage($record)."'",
                    ]),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->orderByRaw('unaccent(lower(last_name)) '.($direction === 'desc' ? 'desc' : 'asc'))
                        ->orderByRaw('unaccent(lower(coalesce(first_name, \'\'))) '.($direction === 'desc' ? 'desc' : 'asc')))
                    ->description(fn (PublicFigure $record) => $record->currentAffiliation()?->label()),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('cards_count')
                    ->label('Cartes')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Monogramme en SVG (initiales en serif dans un carré tracé à l'encre), comme sur le site.
     */
    private static function monogramImage(PublicFigure $figure): string
    {
        $initials = e(Appearance::monogram($figure));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36">'
            .'<rect x="0.5" y="0.5" width="35" height="35" fill="#F7F4EA" stroke="#20212B"/>'
            .'<text x="18" y="24" text-anchor="middle" font-family="Georgia, serif" font-weight="800" font-size="15" fill="#20212B">'.$initials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
