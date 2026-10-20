<?php

namespace App\Filament\Resources\PublicFigures\Pages;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Filament\Resources\PublicFigures\PublicFigureResource;
use App\Filament\Support\DomainAction;
use App\Models\PublicFigure;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPublicFigure extends EditRecord
{
    protected static string $resource = PublicFigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PublicFigure $figure */
        $figure = $this->getRecord();

        return [
            ...$data,
            'affiliations' => $figure->affiliations()->get()
                ->map(fn ($affiliation) => $affiliation->only(['id', 'organization', 'role', 'started_on', 'ended_on']))
                ->all(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PublicFigure $record */
        return DomainAction::inForm(fn () => app(SavePublicFigure::class)->handle($record, auth()->user(), $data));
    }
}
