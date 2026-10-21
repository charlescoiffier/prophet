<?php

namespace App\Filament\Resources\Cards\Schemas;

use App\Models\Card;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Fiche d'une carte, sur le modèle de docs/maquettes/Carte.html.
 */
class CardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->components([
                        TextEntry::make('quote')
                            ->hiddenLabel()
                            ->size('lg')
                            ->weight('semibold'),
                        TextEntry::make('context')
                            ->label('Contexte')
                            ->placeholder('—'),
                        TextEntry::make('testable_statement')
                            ->label('Ce qui est vérifié')
                            ->placeholder('À rédiger'),
                        TextEntry::make('verdict_criterion')
                            ->label('Critère de verdict')
                            ->placeholder('À rédiger'),
                        TextEntry::make('approvedVerdict.value')
                            ->label('Verdict')
                            ->badge()
                            ->visible(fn (Card $record) => $record->approvedVerdict !== null),
                        TextEntry::make('approvedVerdict.justification')
                            ->hiddenLabel()
                            ->visible(fn (Card $record) => $record->approvedVerdict !== null),
                        TextEntry::make('rejection_reason')
                            ->label('Motif du refus')
                            ->color('danger')
                            ->visible(fn (Card $record) => $record->rejection_reason !== null),
                    ]),
                Section::make()
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('publicFigure.name')
                            ->label('Personnalité')
                            ->placeholder(fn (Card $record) => 'À créer : '.$record->requested_figure)
                            ->helperText(fn (Card $record) => self::affiliation($record)),
                        TextEntry::make('requested_themes')
                            ->label('Thèmes demandés par l’auteur')
                            ->visible(fn (Card $record) => filled($record->requested_themes)),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('stated_on')
                            ->label('Déclarée le')
                            ->date('j M Y'),
                        TextEntry::make('deadline')
                            ->label('Échéance')
                            ->date('j M Y')
                            ->placeholder('—')
                            ->helperText(fn (Card $record) => $record->deadline_nature?->label()),
                        TextEntry::make('themes.name')
                            ->label('Thèmes')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                        TextEntry::make('author')
                            ->label('Proposée par')
                            ->state(fn (Card $record) => self::author($record)),
                        TextEntry::make('version')
                            ->label('Version'),
                    ]),
            ]);
    }

    private static function affiliation(Card $record): ?string
    {
        $affiliation = $record->publicFigure?->affiliationOn($record->stated_on);

        return $affiliation === null ? null
            : $affiliation->label().', au moment de la déclaration';
    }

    private static function author(Card $record): string
    {
        $label = $record->author?->username ?? 'Visiteur non inscrit ('.$record->author_email.')';

        return $record->isAuthoredBy(auth()->user()) ? $label.' · votre proposition' : $label;
    }
}
