<?php

namespace App\Filament\Resources\Verdicts\Pages;

use App\Filament\Actions\ApproveVerdictAction;
use App\Filament\Actions\EditVerdictAction;
use App\Filament\Actions\RejectVerdictAction;
use App\Filament\Resources\Verdicts\VerdictResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVerdict extends ViewRecord
{
    protected static string $resource = VerdictResource::class;

    public function getTitle(): string
    {
        return 'Proposition de verdict';
    }

    protected function getHeaderActions(): array
    {
        return [
            ApproveVerdictAction::make(),
            RejectVerdictAction::make(),
            EditVerdictAction::make(),
        ];
    }
}
