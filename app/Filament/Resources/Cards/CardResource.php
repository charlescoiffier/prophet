<?php

namespace App\Filament\Resources\Cards;

use App\Enums\CardStatus;
use App\Filament\Resources\Cards\Pages\CreateCard;
use App\Filament\Resources\Cards\Pages\EditCard;
use App\Filament\Resources\Cards\Pages\ListCards;
use App\Filament\Resources\Cards\Pages\ViewCard;
use App\Filament\Resources\Cards\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\Cards\RelationManagers\SourcesRelationManager;
use App\Filament\Resources\Cards\RelationManagers\VerdictsRelationManager;
use App\Filament\Resources\Cards\Schemas\CardForm;
use App\Filament\Resources\Cards\Schemas\CardInfolist;
use App\Filament\Resources\Cards\Tables\CardsTable;
use App\Models\Card;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'carte';

    protected static ?string $pluralModelLabel = 'cartes';

    protected static ?string $recordTitleAttribute = 'quote';

    public static function form(Schema $schema): Schema
    {
        return CardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CardsTable::configure($table);
    }

    /**
     * Nombre de cartes à relire, affiché dans la barre latérale.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Card::where('status', CardStatus::InModeration)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'info';
    }

    public static function getRelations(): array
    {
        return [
            SourcesRelationManager::class,
            VerdictsRelationManager::class,
            ActivityLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCards::route('/'),
            'create' => CreateCard::route('/create'),
            'view' => ViewCard::route('/{record}'),
            'edit' => EditCard::route('/{record}/edit'),
        ];
    }
}
