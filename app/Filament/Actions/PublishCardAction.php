<?php

namespace App\Filament\Actions;

use App\Actions\Cards\PublishCard;
use App\Filament\Support\DomainAction;
use App\Models\Card;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class PublishCardAction
{
    public static function make(): Action
    {
        return Action::make('publish')
            ->label('Publier')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('publish')
            ->requiresConfirmation()
            ->modalHeading('Publier la carte')
            ->modalDescription(fn (Card $record) => $record->isAuthoredBy(auth()->user())
                ? 'C’est votre propre carte : l’historique public indiquera qu’aucun autre modérateur ne l’a relue.'
                : 'La carte devient visible du public et son auteur est prévenu par e-mail.')
            ->action(fn (Action $action, Card $record, PublishCard $publishCard) => DomainAction::run(
                $action,
                fn () => $publishCard->handle($record, auth()->user()),
                'Carte publiée',
            ));
    }
}
