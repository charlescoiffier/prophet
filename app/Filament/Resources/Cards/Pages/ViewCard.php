<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Filament\Actions\PublishCardAction;
use App\Filament\Actions\RejectCardAction;
use App\Filament\Resources\Cards\CardResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCard extends ViewRecord
{
    protected static string $resource = CardResource::class;

    public function getTitle(): string
    {
        return 'Fiche de la carte';
    }

    protected function getHeaderActions(): array
    {
        return [
            PublishCardAction::make(),
            RejectCardAction::make(),
            EditAction::make(),
        ];
    }
}
