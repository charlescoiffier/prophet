<?php

namespace App\Filament\Resources\Cards\Schemas;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Enums\SourceKind;
use App\Filament\Forms\CardReferenceFields;
use App\Filament\Forms\CategoryField;
use App\Models\Card;
use App\Support\Organizations;
use Filament\Actions\Action;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
                        CardReferenceFields::figure()
                            ->required()
                            ->helperText(fn (?Card $record) => filled($record?->requested_figure) && $record->public_figure_id === null
                                ? 'Demandée par l’auteur : « '.$record->requested_figure.' ». Tapez son nom pour la créer (fiche à compléter ensuite), ou « + » pour la fiche complète.'
                                : 'Un nom absent de la liste crée la personnalité, à compléter ensuite dans sa fiche.')
                            ->createOptionForm([
                                TextInput::make('first_name')->label('Prénom')->maxLength(100),
                                TextInput::make('last_name')->label('Nom')->required()->maxLength(155),
                                CategoryField::make(),
                                TextInput::make('role')->label('Fonction au moment de la déclaration')->maxLength(255),
                                TextInput::make('organization')->label('Organisation')->maxLength(255)->datalist(fn () => Organizations::known()->all()),
                            ])
                            ->createOptionUsing(fn (array $data, SavePublicFigure $savePublicFigure) => DB::transaction(fn () => $savePublicFigure
                                ->handle(null, auth()->user(), [
                                    ...CategoryField::resolve(Arr::except($data, ['role', 'organization']), auth()->user()),
                                    'affiliations' => filled($data['role'] ?? null) ? [['role' => $data['role'], 'organization' => $data['organization'] ?? null]] : [],
                                ])->getKey())),
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
                        CardReferenceFields::themes()
                            ->helperText(fn (?Card $record) => filled($record?->requested_themes)
                                ? 'Demandés par l’auteur : « '.$record->requested_themes.' ». Tapez un thème absent de la liste pour le créer.'
                                : 'Un thème absent de la liste est créé à l’enregistrement.'),
                    ]),
                Repeater::make('sources')
                    ->label('Sources de la citation')
                    ->visibleOn('create')
                    ->columnSpanFull()
                    ->columns(5)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->deleteAction(fn (Action $action) => $action->requiresConfirmation()->modalHeading('Supprimer cette source ?'))
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
