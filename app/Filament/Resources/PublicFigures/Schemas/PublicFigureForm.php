<?php

namespace App\Filament\Resources\PublicFigures\Schemas;

use App\Actions\Categories\CreateCategory;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Rules\NotNearDuplicateOrganization;
use App\Support\Organizations;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Les affiliations ne sont pas liées en « relationship » : SavePublicFigure les enregistre et les journalise.
 */
class PublicFigureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Fiche')
                    ->columnSpan(2)
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // L'identifiant d'URL suit le nom, tant qu'il n'a pas été modifié à la main.
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Identifiant d’URL')
                            ->helperText('Suit le nom automatiquement ; le modifier change l’adresse de la page.')
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('Catégorie')
                            ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nouvelle catégorie')->required()->maxLength(100),
                            ])
                            ->createOptionUsing(fn (array $data, CreateCategory $createCategory) => $createCategory
                                ->handle(auth()->user(), $data['name'])->getKey()),
                        TextInput::make('description')
                            ->label('Fonction actuelle')
                            ->maxLength(255),
                        TextInput::make('wikidata_id')
                            ->label('Identifiant Wikidata')
                            ->placeholder('Q12345')
                            ->helperText('Évite les homonymes.')
                            ->regex('/^Q\d+$/')
                            ->unique(ignoreRecord: true),
                        TextInput::make('photo_url')
                            ->label('Ou lien direct vers une photo')
                            ->helperText('Le fichier image lui-même (.jpg, .png, .webp), pas la page qui le montre. Utilisé seulement s’il n’y a pas de photo téléversée.')
                            ->url()
                            ->regex(PublicFigure::PHOTO_URL_PATTERN)
                            ->validationMessages(['regex' => __('validation.custom.photo_url.image')]),
                    ]),
                Section::make('Photo')
                    ->columnSpan(1)
                    ->components([
                        FileUpload::make('photo_path')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('portraits')
                            ->visibility('public')
                            // Pas d'éditeur d'image : celui de Filament 5 plante à l'enregistrement
                            // (getCroppedCanvas). Le recadrage au carré se fait automatiquement à l'envoi.
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('600')
                            ->maxSize(5120)
                            ->helperText('Photo libre de droits uniquement (crédit et licence vérifiés). Recadrée au carré, affichée en noir et blanc.'),
                    ]),
                Repeater::make('affiliations')
                    ->label('Affiliations successives')
                    ->helperText('Parti, média ou gouvernement : on affiche celle en cours au moment de chaque déclaration.')
                    ->columnSpanFull()
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter une affiliation')
                    ->schema([
                        Hidden::make('id'),
                        Select::make('organization')
                            ->label('Organisation')
                            ->searchable()
                            ->required()
                            // Organisations déjà connues, plus la valeur en cours (même si elle vient d'être créée).
                            ->options(fn (?string $state) => Organizations::known()
                                ->when($state, fn ($known) => $known->push($state))
                                ->unique()
                                ->mapWithKeys(fn (string $name) => [$name => $name])
                                ->all())
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nouvelle organisation')
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(fn (Get $get) => [new NotNearDuplicateOrganization((bool) $get('distinct'))]),
                                Checkbox::make('distinct')
                                    ->label('C’est une autre organisation (pas une faute de frappe)'),
                            ])
                            ->createOptionUsing(fn (array $data) => Organizations::canonical($data['name'])),
                        TextInput::make('role')->label('Rôle'),
                        DatePicker::make('started_on')->label('Début'),
                        DatePicker::make('ended_on')->label('Fin')->afterOrEqual('started_on'),
                    ]),
            ]);
    }
}
