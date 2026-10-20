<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Actions\ChangeUserRoleAction;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Comptes inscrits, visibles de l'administrateur seul : il nomme les modérateurs.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'compte';

    protected static ?string $pluralModelLabel = 'comptes';

    protected static ?string $recordTitleAttribute = 'username';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('username')->label('Pseudo')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Rôle')->badge()->color(fn (Role $state) => $state === Role::Member ? 'gray' : 'primary'),
                TextColumn::make('email_verified_at')->label('E-mail confirmé')->date('j M Y')->placeholder('Non'),
                TextColumn::make('created_at')->label('Inscrit le')->date('j M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options(Role::class),
            ])
            ->recordActions([
                ChangeUserRoleAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
