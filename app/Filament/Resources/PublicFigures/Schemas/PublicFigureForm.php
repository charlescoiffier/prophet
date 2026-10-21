<?php

namespace App\Filament\Resources\PublicFigures\Schemas;

use App\Filament\Forms\CategoryField;
use App\Filament\Forms\Components\PortraitStudio;
use App\Models\PublicFigure;
use App\Rules\NotNearDuplicateOrganization;
use App\Support\Names;
use App\Support\Organizations;
use Filament\Actions\Action;
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
                        CategoryField::make(),
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
                    ->label('Fonctions successives')
                    ->helperText('La fonction en cours au moment de chaque déclaration est affichée avec la carte ; celle en cours aujourd’hui, sur la page de la personnalité.')
                    ->columnSpanFull()
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter une fonction')
                    ->deleteAction(fn (Action $action) => $action->requiresConfirmation()->modalHeading('Supprimer cette fonction ?'))
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('role')
                            ->label('Fonction')
                            ->placeholder('Députée, ministre, éditorialiste…')
                            ->required()
                            ->maxLength(255),
                        Select::make('organization')
                            ->label('Organisation')
                            ->placeholder('Facultative')
                            ->searchable()
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
                        DatePicker::make('started_on')->label('Début'),
                        DatePicker::make('ended_on')->label('Fin')->afterOrEqual('started_on'),
                        Checkbox::make('distinct')
                            ->label('C’est une autre organisation (pas une faute de frappe)')
                            ->helperText(fn (Get $get) => 'Proche de « '.implode(' », « ', Organizations::nearDuplicates((string) $get('organization'))).' ».')
                            ->visible(fn (Get $get) => filled($get('organization')) && ! Organizations::isKnown($get('organization'))
                                && Organizations::nearDuplicates($get('organization')) !== [])
                            ->dehydrated(false)
                            ->columnSpanFull(),
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
