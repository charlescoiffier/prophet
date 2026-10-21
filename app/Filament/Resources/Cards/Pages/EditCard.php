<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Actions\Cards\UpdateCard;
use App\Filament\Forms\CardReferenceFields;
use App\Filament\Resources\Cards\CardResource;
use App\Filament\Support\DomainAction;
use App\Models\Card;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    public function getTitle(): string
    {
        return 'Modifier la rédaction';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Card $card */
        $card = $this->getRecord();

        return [...$data, 'themes' => $card->themes()->pluck('themes.id')->all()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Card $record */
        // Personnalité et thèmes tapés : créés avec la modification (même transaction).
        return DomainAction::inForm(fn () => DB::transaction(fn () => app(UpdateCard::class)
            ->handle($record, auth()->user(), CardReferenceFields::resolve($data, auth()->user()))));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
