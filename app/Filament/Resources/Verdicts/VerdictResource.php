<?php

namespace App\Filament\Resources\Verdicts;

use App\Enums\VerdictStatus;
use App\Filament\Resources\Verdicts\Pages\ListVerdicts;
use App\Filament\Resources\Verdicts\Pages\ViewVerdict;
use App\Filament\Resources\Verdicts\Schemas\VerdictInfolist;
use App\Filament\Resources\Verdicts\Tables\VerdictsTable;
use App\Models\Verdict;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Les verdicts sont proposés par les membres ; ici on les valide, les corrige ou les refuse.
 */
class VerdictResource extends Resource
{
    protected static ?string $model = Verdict::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'verdict';

    protected static ?string $pluralModelLabel = 'verdicts';

    public static function infolist(Schema $schema): Schema
    {
        return VerdictInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerdictsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Verdict::where('status', VerdictStatus::Proposed)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerdicts::route('/'),
            'view' => ViewVerdict::route('/{record}'),
        ];
    }
}
