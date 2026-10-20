<?php

namespace App\Filament\Resources\Verdicts\Pages;

use App\Enums\VerdictStatus;
use App\Filament\Resources\Verdicts\VerdictResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVerdicts extends ListRecords
{
    protected static string $resource = VerdictResource::class;

    public function getTabs(): array
    {
        return [
            'proposed' => $this->statusTab('À valider', VerdictStatus::Proposed),
            'approved' => $this->statusTab('Validés', VerdictStatus::Approved),
            'rejected' => $this->statusTab('Refusés', VerdictStatus::Rejected),
            'all' => Tab::make('Tous'),
        ];
    }

    private function statusTab(string $label, VerdictStatus $status): Tab
    {
        return Tab::make($label)->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
    }
}
