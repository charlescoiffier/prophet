<?php

namespace App\Filament\Widgets;

use App\Enums\CardStatus;
use App\Enums\ReportStatus;
use App\Enums\VerdictStatus;
use App\Filament\Resources\Cards\CardResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Verdicts\VerdictResource;
use App\Models\Card;
use App\Models\Report;
use App\Models\Verdict;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * « Tout à traiter », comme en tête de la maquette de modération.
 */
class ModerationOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        return [
            Stat::make('Cartes à relire', Card::where('status', CardStatus::InModeration)->count())
                ->color('info')
                ->url(CardResource::getUrl('index', ['tab' => 'to_review'])),
            Stat::make('Verdicts à valider', Verdict::where('status', VerdictStatus::Proposed)->count())
                ->color('warning')
                ->url(VerdictResource::getUrl('index', ['tab' => 'proposed'])),
            Stat::make('Signalements ouverts', Report::where('status', ReportStatus::Open)->count())
                ->color('danger')
                ->url(ReportResource::getUrl('index')),
            Stat::make('Échéances dépassées sans verdict', Card::awaitingVerdict()->count())
                ->url(CardResource::getUrl('index', ['tab' => 'awaiting_verdict'])),
        ];
    }
}
