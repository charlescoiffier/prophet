<?php

namespace App\Filament\Resources\PublicFigures;

use App\Filament\Resources\PublicFigures\Pages\CreatePublicFigure;
use App\Filament\Resources\PublicFigures\Pages\EditPublicFigure;
use App\Filament\Resources\PublicFigures\Pages\ListPublicFigures;
use App\Filament\Resources\PublicFigures\Schemas\PublicFigureForm;
use App\Filament\Resources\PublicFigures\Tables\PublicFiguresTable;
use App\Models\PublicFigure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PublicFigureResource extends Resource
{
    protected static ?string $model = PublicFigure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'personnalité';

    protected static ?string $pluralModelLabel = 'personnalités';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PublicFigureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicFiguresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicFigures::route('/'),
            'create' => CreatePublicFigure::route('/create'),
            'edit' => EditPublicFigure::route('/{record}/edit'),
        ];
    }
}
