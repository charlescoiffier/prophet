<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Actions\Cards\CreateCard as CreateCardAction;
use App\Filament\Forms\CardReferenceFields;
use App\Filament\Resources\Cards\CardResource;
use App\Filament\Support\DomainAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        // Personnalité et thèmes tapés : créés avec la carte (même transaction).
        return DomainAction::inForm(fn () => DB::transaction(fn () => app(CreateCardAction::class)
            ->handle(auth()->user(), CardReferenceFields::resolve($data, auth()->user()))));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return auth()->user()->isAdmin()
            ? 'Carte enregistrée, en modération : vous pouvez la publier'
            : 'Carte enregistrée : un autre modérateur doit la publier';
    }
}
