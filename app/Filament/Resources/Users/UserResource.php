<?php

namespace App\Filament\Resources\Users;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\RestoreUser;
use App\Enums\Role;
use App\Filament\Actions\ChangeUserRoleAction;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                TextColumn::make('deleted_at')->label('Supprimé le')->date('j M Y')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options(Role::class),
                TrashedFilter::make()->label('Comptes supprimés'),
            ])
            ->recordActions([
                ChangeUserRoleAction::make(),
                Action::make('deleteAccount')
                    ->label('Supprimer')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer le compte')
                    ->modalDescription('Le compte ne pourra plus se connecter (sessions et jetons d’API révoqués). Ses cartes et l’historique restent ; le compte peut être restauré.')
                    ->action(fn (User $record, DeleteUser $deleteUser) => $deleteUser->handle($record, auth()->user()))
                    ->successNotificationTitle('Compte supprimé'),
                Action::make('restoreAccount')
                    ->label('Restaurer')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->authorize('restore')
                    ->requiresConfirmation()
                    ->action(fn (User $record, RestoreUser $restoreUser) => $restoreUser->handle($record, auth()->user()))
                    ->successNotificationTitle('Compte restauré'),
            ]);
    }

    /**
     * Les comptes supprimés restent listés (filtre « Comptes supprimés ») pour pouvoir être restaurés.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
