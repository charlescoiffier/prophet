<?php

namespace App\Filament\Forms\Components;

use App\Models\PublicFigure;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Atelier portrait : le navigateur recadre, découpe et passe la photo en deux tons, puis remet
 * ici l'image finale (PNG), la photo d'origine et les réglages. Les fichiers vont sur le disque
 * « public » au moment de l'enregistrement.
 *
 * @phpstan-type PortraitState array{path: ?string, original_path: ?string, settings: ?array<string, mixed>, image: TemporaryUploadedFile|string|null, original: TemporaryUploadedFile|string|null}
 */
class PortraitStudio extends Field
{
    protected string $view = 'filament.forms.components.portrait-studio';

    private const DIRECTORY = 'portraits';

    private const ORIGINALS_DIRECTORY = 'portraits/originals';

    /** Types acceptés pour la photo d'origine, avec l'extension donnée au fichier. */
    private const ORIGINAL_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(self::stateFromRecord(null));

        // Une nouvelle photo remplace le lien : on le vide, pour qu'un ancien lien invalide
        // ne bloque pas l'enregistrement.
        $this->live();
        $this->afterStateUpdated(function (Set $set, ?array $state): void {
            if (($state['image'] ?? null) instanceof TemporaryUploadedFile) {
                $set('photo_url', null);
            }
        });

        $this->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
            $image = $value['image'] ?? null;
            $original = $value['original'] ?? null;

            if ($image instanceof TemporaryUploadedFile && $image->getMimeType() !== 'image/png') {
                $fail(__('validation.custom.photo.image'));
            }
            if ($original instanceof TemporaryUploadedFile && ! array_key_exists((string) $original->getMimeType(), self::ORIGINAL_TYPES)) {
                $fail(__('validation.custom.photo.image'));
            }
        }]);

        // Avant la lecture des données : les fichiers sont rangés et l'état réécrit, pour qu'un nouvel
        // enregistrement de la même page ne les range pas une seconde fois.
        $this->beforeStateDehydrated(function (PortraitStudio $component, ?array $state): void {
            $record = $component->getRecord();
            $component->state(self::store($state ?? [], $record instanceof PublicFigure ? $record : null));
        }, shouldUpdateValidatedStateAfter: true);
    }

    /**
     * @return PortraitState
     */
    public static function stateFromRecord(?PublicFigure $figure): array
    {
        return [
            'path' => $figure?->photo_path,
            'original_path' => $figure?->photo_original_path,
            'settings' => $figure?->photo_settings,
            'image' => null,
            'original' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{photo_path: ?string, photo_original_path: ?string, photo_settings: ?array<string, mixed>}
     */
    public static function toAttributes(array $state): array
    {
        return [
            'photo_path' => $state['path'] ?? null,
            'photo_original_path' => $state['original_path'] ?? null,
            'photo_settings' => $state['settings'] ?? null,
        ];
    }

    /**
     * Données du formulaire pour SavePublicFigure : l'état du champ devient les trois colonnes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeInto(array $data): array
    {
        $photo = $data['photo'] ?? self::stateFromRecord(null);
        unset($data['photo']);

        return [...$data, ...self::toAttributes($photo)];
    }

    /**
     * Range les fichiers remis par le navigateur. Les chemins ne viennent jamais du navigateur :
     * ce sont les fichiers qu'on vient de ranger, rien si la photo a été retirée, sinon ceux de la fiche.
     * Un original ou des réglages sans nouvelle image ne changent rien.
     *
     * @param  array<string, mixed>  $state
     * @return PortraitState
     */
    private static function store(array $state, ?PublicFigure $figure): array
    {
        $current = self::stateFromRecord($figure);
        $image = $state['image'] ?? null;
        $original = $state['original'] ?? null;

        if (! $image instanceof TemporaryUploadedFile) {
            $removed = array_key_exists('path', $state) && $state['path'] === null;

            return $removed ? self::stateFromRecord(null) : $current;
        }

        $originalPath = $current['original_path'];
        if ($original instanceof TemporaryUploadedFile) {
            $extension = self::ORIGINAL_TYPES[$original->getMimeType()];
            $originalPath = $original->storeAs(self::ORIGINALS_DIRECTORY, Str::random(40).'.'.$extension, 'public');
        }

        return [
            'path' => $image->storeAs(self::DIRECTORY, Str::random(40).'.png', 'public'),
            'original_path' => $originalPath,
            'settings' => is_array($state['settings'] ?? null) ? $state['settings'] : null,
            'image' => null,
            'original' => null,
        ];
    }

    public function getImageUrl(): ?string
    {
        $state = $this->getState();
        $image = $state['image'] ?? null;

        if ($image instanceof TemporaryUploadedFile) {
            return $image->isPreviewable() ? $image->temporaryUrl() : null;
        }

        return filled($state['path'] ?? null) ? Storage::disk('public')->url($state['path']) : null;
    }

    public function getOriginalUrl(): ?string
    {
        $path = $this->getState()['original_path'] ?? null;

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Taille finale, nuances et couleurs pour une nouvelle photo (une photo retouchée garde les siennes).
     *
     * @return array{size: int, shades: int, palette: array{dark: string, light: string, halo: string}}
     */
    public function getStudioDefaults(): array
    {
        return json_decode((string) file_get_contents(resource_path('portrait/studio.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
