<?php

namespace App\Filament\Actions;

use App\Actions\Users\ChangeUserRole;
use App\Enums\Role;
use App\Filament\Support\DomainAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

class ChangeUserRoleAction
{
    public static function make(): Action
    {
        return Action::make('changeRole')
            ->label('Changer le rôle')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->authorize('changeRole')
            ->fillForm(fn (User $record) => ['role' => $record->role->value])
            ->schema([
                Select::make('role')
                    ->label('Rôle')
                    ->options(Role::class)
                    ->required(),
            ])
            ->action(fn (Action $action, User $record, array $data, ChangeUserRole $changeUserRole) => DomainAction::run(
                $action,
                fn () => $changeUserRole->handle($record, auth()->user(), $data['role'] instanceof Role ? $data['role'] : Role::from($data['role'])),
                'Rôle modifié',
            ));
    }
}
