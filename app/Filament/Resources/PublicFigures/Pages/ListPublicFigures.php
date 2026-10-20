<?php

namespace App\Filament\Resources\PublicFigures\Pages;

use App\Filament\Resources\PublicFigures\PublicFigureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicFigures extends ListRecords
{
    protected static string $resource = PublicFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
