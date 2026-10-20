<?php

namespace App\Filament\Resources\PublicFigures\Schemas;

use App\Enums\PublicFigureCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Les affiliations ne sont pas liées en « relationship » : SavePublicFigure les enregistre et les journalise.
 */
class PublicFigureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fiche')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Select::make('category')
                            ->label('Catégorie')
                            ->options(PublicFigureCategory::class)
                            ->required(),
                        TextInput::make('description')
                            ->label('Fonction actuelle')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Identifiant d’URL')
                            ->helperText('Laisser vide pour le déduire du nom.')
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('wikidata_id')
                            ->label('Identifiant Wikidata')
                            ->placeholder('Q12345')
                            ->helperText('Évite les homonymes.')
                            ->regex('/^Q\d+$/')
                            ->unique(ignoreRecord: true),
                        TextInput::make('photo_url')
                            ->label('Photo (libre de droits)')
                            ->url(),
                    ]),
                Repeater::make('affiliations')
                    ->label('Affiliations successives')
                    ->helperText('Parti, média ou gouvernement : on affiche celle en cours au moment de chaque déclaration.')
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter une affiliation')
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('organization')->label('Organisation')->required(),
                        TextInput::make('role')->label('Rôle'),
                        DatePicker::make('started_on')->label('Début'),
                        DatePicker::make('ended_on')->label('Fin')->afterOrEqual('started_on'),
                    ]),
            ]);
    }
}
