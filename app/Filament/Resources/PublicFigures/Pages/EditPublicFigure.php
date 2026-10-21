<?php

namespace App\Filament\Resources\PublicFigures\Pages;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Filament\Forms\CategoryField;
use App\Filament\Forms\Components\PortraitStudio;
use App\Filament\Resources\PublicFigures\PublicFigureResource;
use App\Filament\Support\DomainAction;
use App\Models\PublicFigure;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditPublicFigure extends EditRecord
{
    protected static string $resource = PublicFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PublicFigure $figure */
        $figure = $this->getRecord();

        return [
            ...$data,
            'photo' => PortraitStudio::stateFromRecord($figure),
            'affiliations' => $figure->affiliations()->get()
                ->map(fn ($affiliation) => $affiliation->only(['id', 'role', 'organization', 'started_on', 'ended_on']))
                ->all(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PublicFigure $record */
        // Une catégorie tapée n'est créée qu'avec la fiche (même transaction).
        return DomainAction::inForm(fn () => DB::transaction(fn () => app(SavePublicFigure::class)
            ->handle($record, auth()->user(), CategoryField::resolve(PortraitStudio::mergeInto($data), auth()->user()))));
    }
}
