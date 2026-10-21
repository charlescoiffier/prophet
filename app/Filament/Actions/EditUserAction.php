<?php

namespace App\Filament\Actions;

use App\Actions\Users\ChangeUserRole;
use App\Actions\Users\UpdateAccount;
use App\Enums\Role;
use App\Filament\Support\DomainAction;
use App\Http\Requests\Api\V1\AccountRules;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Fiche d'un compte : prénom, nom, pseudo, adresse et rôle, avec les dates du compte en lecture seule.
 */
class EditUserAction
{
    public static function make(): Action
    {
        return Action::make('editAccount')
            ->label('Modifier')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->authorize('update')
            ->modalHeading(fn (User $record) => 'Compte de '.$record->username)
            ->modalSubmitActionLabel('Enregistrer')
            ->fillForm(fn (User $record) => [
                'first_name' => $record->first_name,
                'last_name' => $record->last_name,
                'username' => $record->username,
                'email' => $record->email,
                'role' => $record->role->value,
            ])
            ->schema(fn (User $record) => [
                Grid::make(2)->schema([
                    TextInput::make('first_name')->label('Prénom')->maxLength(100),
                    TextInput::make('last_name')->label('Nom')->maxLength(100),
                    TextInput::make('username')->label('Pseudo')->rules(AccountRules::profile($record)['username'])->required(),
                    TextInput::make('email')->label('E-mail')->email()->rules(AccountRules::profile($record)['email'])->required()
                        ->helperText('Une nouvelle adresse devra être confirmée : un lien part par e-mail.'),
                    Select::make('role')
                        ->label('Rôle')
                        ->options(Role::class)
                        ->required()
                        ->disabled(fn () => ! Gate::allows('changeRole', $record))
                        ->helperText(fn () => Gate::allows('changeRole', $record) ? null : 'Vous ne pouvez pas changer votre propre rôle.'),
                    TextEntry::make('email_verified_at')->label('E-mail confirmé')
                        ->state($record->email_verified_at?->translatedFormat('j F Y à H:i') ?? 'Non'),
                    TextEntry::make('created_at')->label('Inscrit le')
                        ->state($record->created_at->translatedFormat('j F Y à H:i')),
                    TextEntry::make('deleted_at')->label('Supprimé le')
                        ->state($record->deleted_at?->translatedFormat('j F Y à H:i') ?? '—'),
                ]),
            ])
            ->action(fn (Action $action, User $record, array $data, UpdateAccount $updateAccount, ChangeUserRole $changeUserRole) => DomainAction::run(
                $action,
                fn () => DB::transaction(function () use ($record, $data, $updateAccount, $changeUserRole) {
                    $updateAccount->handle($record, auth()->user(), $data);

                    $role = isset($data['role']) ? ($data['role'] instanceof Role ? $data['role'] : Role::from($data['role'])) : $record->role;

                    if ($role !== $record->role) {
                        $changeUserRole->handle($record, auth()->user(), $role);
                    }
                }),
                'Compte enregistré',
            ));
    }
}
