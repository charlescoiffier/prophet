<?php

namespace App\Filament\Resources\Cards\Schemas;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\SourceKind;
use App\Models\Card;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Models\Theme;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Thèmes et sources ne sont pas liés en « relationship » : c'est l'action
 * (CreateCard / UpdateCard) qui les enregistre et les journalise.
 */
class CardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Déclaration')
                    ->columnSpan(2)
                    ->columns(2)
                    ->components([
                        Textarea::make('quote')
                            ->label('Citation')
                            ->helperText('Mot pour mot, entre guillemets.')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('context')
                            ->label('Contexte')
                            ->helperText('Quelques phrases autour de la citation.')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('testable_statement')
                            ->label('Ce qui est vérifié')
                            ->helperText('Reformulation testable de la promesse ou de la prédiction.')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('verdict_criterion')
                            ->label('Critère de verdict')
                            ->helperText('Comment on tranchera, et avec quelle donnée.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Métadonnées')
                    ->columnSpan(1)
                    ->components([
                        Select::make('public_figure_id')
                            ->label('Personnalité')
                            ->options(fn () => PublicFigure::alphabetical()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText(fn (?Card $record) => filled($record?->requested_figure) && $record->public_figure_id === null
                                ? 'Demandée par l’auteur : « '.$record->requested_figure.' ». Créez sa fiche avec « + », puis choisissez-la.'
                                : null)
                            ->createOptionForm([
                                TextInput::make('first_name')->label('Prénom')->maxLength(100),
                                TextInput::make('last_name')->label('Nom')->required()->maxLength(155),
                                Select::make('category_id')->label('Catégorie')->options(fn () => Category::orderBy('name')->pluck('name', 'id'))->required(),
                                TextInput::make('description')->label('Fonction actuelle'),
                            ])
                            ->createOptionUsing(fn (array $data, SavePublicFigure $savePublicFigure) => $savePublicFigure
                                ->handle(null, auth()->user(), $data)->getKey()),
                        ToggleButtons::make('type')
                            ->label('Type')
                            ->options(CardType::class)
                            ->inline()
                            ->required(),
                        DatePicker::make('stated_on')
                            ->label('Déclarée le')
                            ->required()
                            ->maxDate(now()),
                        DatePicker::make('deadline')
                            ->label('Échéance'),
                        Select::make('deadline_nature')
                            ->label('Nature de l’échéance')
                            ->options(DeadlineNature::class)
                            ->requiredWith('deadline'),
                        Select::make('themes')
                            ->label('Thèmes')
                            ->multiple()
                            ->options(fn () => Theme::orderBy('name')->pluck('name', 'id'))
                            ->helperText(fn (?Card $record) => filled($record?->requested_themes)
                                ? 'Demandés par l’auteur : « '.$record->requested_themes.' » (à créer dans Thèmes s’ils sont utiles).'
                                : null),
                    ]),
                Repeater::make('sources')
                    ->label('Sources de la citation')
                    ->visibleOn('create')
                    ->columnSpanFull()
                    ->columns(5)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Ajouter une source')
                    ->schema(self::sourceFields()),
            ]);
    }

    /**
     * Champs d'une source, partagés avec le gestionnaire des sources.
     *
     * @return list<Component|Field>
     */
    public static function sourceFields(): array
    {
        return [
            TextInput::make('url')
                ->label('Lien')
                ->url()
                ->required()
                ->columnSpan(2),
            Select::make('kind')
                ->label('Nature')
                ->options(SourceKind::class)
                ->required(),
            TextInput::make('outlet')
                ->label('Média'),
            DatePicker::make('published_on')
                ->label('Publiée le'),
            TextInput::make('timecode')
                ->label('Horodatage')
                ->placeholder('12:34')
                ->helperText('Pour une vidéo ou un audio.')
                ->regex('/^\d{1,2}(:\d{2}){1,2}$/'),
        ];
    }
}
