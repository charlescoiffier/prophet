<?php

namespace App\Filament\Resources\Cards\Pages;

use App\Enums\CardStatus;
use App\Filament\Resources\Cards\CardResource;
use App\Models\Card;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCards extends ListRecords
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Saisir une carte'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'to_review' => $this->statusTab('À relire', CardStatus::InModeration),
            'awaiting_verdict' => Tab::make('Verdict attendu')
                ->modifyQueryUsing(fn (Builder $query) => $query->awaitingVerdict())
                ->badge(Card::awaitingVerdict()->count() ?: null),
            'published' => $this->statusTab('Publiées', CardStatus::Published),
            'verdict_proposed' => $this->statusTab('Verdict proposé', CardStatus::VerdictProposed),
            'closed' => $this->statusTab('Tranchées', CardStatus::Closed),
            'rejected' => $this->statusTab('Refusées', CardStatus::Rejected),
            'awaiting_email' => $this->statusTab('E-mail à confirmer', CardStatus::AwaitingEmail),
            'all' => Tab::make('Toutes'),
        ];
    }

    private function statusTab(string $label, CardStatus $status): Tab
    {
        return Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status))
            ->badge(in_array($status, [CardStatus::InModeration, CardStatus::VerdictProposed], true)
                ? (Card::where('status', $status)->count() ?: null)
                : null);
    }
}
