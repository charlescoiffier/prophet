<?php

namespace App\Filament\Resources\PublicFigures\Pages;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Filament\Forms\CategoryField;
use App\Filament\Forms\Components\PortraitStudio;
use App\Filament\Resources\PublicFigures\PublicFigureResource;
use App\Filament\Support\DomainAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePublicFigure extends CreateRecord
{
    protected static string $resource = PublicFigureResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Une catégorie tapée n'est créée qu'avec la fiche (même transaction).
        return DomainAction::inForm(fn () => DB::transaction(fn () => app(SavePublicFigure::class)
            ->handle(null, auth()->user(), CategoryField::resolve(PortraitStudio::mergeInto($data), auth()->user()))));
    }
}
