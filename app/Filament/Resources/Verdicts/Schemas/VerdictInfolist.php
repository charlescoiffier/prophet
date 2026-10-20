<?php

namespace App\Filament\Resources\Verdicts\Schemas;

use App\Filament\Resources\Cards\CardResource;
use App\Models\Verdict;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VerdictInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->components([
                        TextEntry::make('card.quote')
                            ->label('Carte')
                            ->weight('semibold')
                            ->url(fn (Verdict $record) => CardResource::getUrl('view', ['record' => $record->card])),
                        TextEntry::make('card.verdict_criterion')
                            ->label('Critère de verdict')
                            ->placeholder('Non rédigé'),
                        TextEntry::make('value')
                            ->label('Verdict proposé')
                            ->badge(),
                        TextEntry::make('justification')
                            ->label('Justification'),
                        RepeatableEntry::make('sources')
                            ->label('Sources du verdict')
                            ->schema([
                                TextEntry::make('url')
                                    ->hiddenLabel()
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->helperText(fn ($record) => trim(($record->outlet ?? '').' · '.$record->kind->label(), ' ·')),
                            ]),
                        TextEntry::make('rejection_reason')
                            ->label('Motif du refus')
                            ->color('danger')
                            ->visible(fn (Verdict $record) => $record->rejection_reason !== null),
                    ]),
                Section::make()
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('card.publicFigure.name')->label('Personnalité'),
                        TextEntry::make('card.deadline')->label('Échéance')->date('j M Y'),
                        TextEntry::make('proposer.username')
                            ->label('Proposé par')
                            ->helperText(fn (Verdict $record) => $record->proposed_by === auth()->id()
                                ? 'Votre proposition : un autre modérateur doit la trancher.'
                                : null),
                        TextEntry::make('created_at')->label('Proposé le')->date('j M Y'),
                        TextEntry::make('decider.username')->label('Tranché par')->placeholder('—'),
                        TextEntry::make('decided_at')->label('Tranché le')->date('j M Y')->placeholder('—'),
                    ]),
            ]);
    }
}
