<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Actions\Cards\CreateCard as CreateCardAction;
use App\Filament\Resources\Cards\CardResource;
use App\Filament\Support\DomainAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCard extends CreateRecord
{
    protected static string $resource = CardResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Saisir une carte';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DomainAction::inForm(fn () => app(CreateCardAction::class)->handle(auth()->user(), $data));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Carte enregistrée : un autre modérateur doit la publier';
    }
}
