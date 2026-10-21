<?php

namespace App\Filament\Resources\PublicFigures\Schemas;

use App\Actions\Categories\CreateCategory;
use App\Filament\Forms\Components\PortraitStudio;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Rules\NotNearDuplicateOrganization;
use App\Support\Names;
use App\Support\Organizations;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
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
                        TextInput::make('first_name')
                            ->label('Prénom')
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $old) => self::followSlug($get, $set, Names::full($old, $get('last_name')))),
                        TextInput::make('last_name')
                            ->label('Nom')
                            ->helperText('Ou le nom seul, sans prénom.')
                            ->required()
                            ->maxLength(155)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $old) => self::followSlug($get, $set, Names::full($get('first_name'), $old))),
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
                        PortraitStudio::make('photo')
                            ->hiddenLabel()
                            ->helperText('Photo libre de droits uniquement (crédit et licence vérifiés). Recadrée, découpée et passée en deux tons dans l’atelier.'),
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
                            // Organisations déjà connues, plus la valeur en cours.
                            ->options(fn (?string $state) => Organizations::known()
                                ->when($state, fn ($known) => $known->push($state))
                                ->unique()
                                ->mapWithKeys(fn (string $name) => [$name => $name])
                                ->all())
                            // Un texte inconnu devient une nouvelle organisation, sans fenêtre de création.
                            ->getSearchResultsUsing(fn (string $search) => Organizations::suggestions($search))
                            ->getOptionLabelUsing(fn (?string $value) => $value)
                            ->live()
                            ->rules(fn (Get $get) => [new NotNearDuplicateOrganization((bool) $get('distinct'))]),
                        Checkbox::make('distinct')
                            ->label('C’est une autre organisation (pas une faute de frappe)')
                            ->helperText(fn (Get $get) => 'Proche de « '.implode(' », « ', Organizations::nearDuplicates((string) $get('organization'))).' ».')
                            ->visible(fn (Get $get) => filled($get('organization')) && ! Organizations::isKnown($get('organization'))
                                && Organizations::nearDuplicates($get('organization')) !== [])
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('role')->label('Rôle'),
                        DatePicker::make('started_on')->label('Début'),
                        DatePicker::make('ended_on')->label('Fin')->afterOrEqual('started_on'),
                    ]),
            ]);
    }

    /**
     * L'identifiant d'URL suit « Prénom Nom », tant qu'il n'a pas été modifié à la main.
     */
    private static function followSlug(Get $get, Set $set, string $previousName): void
    {
        if (blank($get('slug')) || $get('slug') === Str::slug($previousName)) {
            $set('slug', Str::slug(Names::full($get('first_name'), $get('last_name'))));
        }
    }
}
