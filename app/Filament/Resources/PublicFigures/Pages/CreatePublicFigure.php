<?php

namespace App\Filament\Resources\PublicFigures\Pages;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Filament\Forms\Components\PortraitStudio;
use App\Filament\Resources\PublicFigures\PublicFigureResource;
use App\Filament\Support\DomainAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePublicFigure extends CreateRecord
{
    protected static string $resource = PublicFigureResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DomainAction::inForm(fn () => app(SavePublicFigure::class)->handle(null, auth()->user(), PortraitStudio::mergeInto($data)));
    }
}
