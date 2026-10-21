<?php

namespace App\Filament\Forms;

use App\Actions\PublicFigures\SavePublicFigure;
use App\Actions\Themes\CreateTheme;
use App\Models\Category;
use App\Models\PublicFigure;
use App\Models\Theme;
use App\Models\User;
use App\Support\Names;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;

/**
 * Personnalité et thèmes d'une carte en modération : on les choisit, ou on tape un nom qui n'existe pas
 * encore. Ils sont créés à l'enregistrement de la carte ; la personnalité, avec la catégorie « Autre »,
 * se complète ensuite dans sa fiche.
 */
class CardReferenceFields
{
    /** Préfixe d'une valeur tapée qui n'existe pas encore. */
    private const NEW = 'nouveau:';

    public static function figure(): Select
    {
        return Select::make('public_figure_id')
            ->label('Personnalité')
            ->options(fn () => PublicFigure::alphabetical()->pluck('name', 'id'))
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => self::suggestions(
                PublicFigure::alphabetical()->get(['id', 'name']), $search, 'nouvelle personnalité, à compléter ensuite'))
            ->getOptionLabelUsing(fn (?string $value) => self::isNew($value)
                ? self::typed($value).' (nouvelle personnalité)'
                : PublicFigure::find($value)?->name);
    }

    public static function themes(): Select
    {
        return Select::make('themes')
            ->label('Thèmes')
            ->multiple()
            ->options(fn () => Theme::orderBy('name')->pluck('name', 'id'))
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => self::suggestions(Theme::orderBy('name')->get(['id', 'name']), $search, 'nouveau thème'))
            ->getOptionLabelsUsing(fn (array $values) => collect($values)
                ->mapWithKeys(fn (string $value) => [$value => self::isNew($value)
                    ? self::typed($value).' (nouveau thème)'
                    : Theme::find($value)?->name])
                ->all());
    }

    /**
     * Crée la personnalité et les thèmes tapés, et les remplace par leurs identifiants.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolve(array $data, User $actor): array
    {
        if (self::isNew($data['public_figure_id'] ?? null)) {
            $name = self::typed($data['public_figure_id']);
            $existing = PublicFigure::all(['id', 'name'])->first(fn (PublicFigure $figure) => Names::normalize($figure->name) === Names::normalize($name));

            $data['public_figure_id'] = ($existing ?? app(SavePublicFigure::class)->handle(null, $actor, [
                'name' => $name,
                'category_id' => Category::where('slug', 'autre')->value('id') ?? Category::orderBy('name')->value('id'),
            ]))->getKey();
        }

        if (isset($data['themes'])) {
            $data['themes'] = collect($data['themes'])
                ->map(fn (string $value) => self::isNew($value) ? app(CreateTheme::class)->handle($actor, self::typed($value))->getKey() : $value)
                ->unique()
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @param  Collection<int, PublicFigure|Theme>  $records
     * @return array<string, string>
     */
    private static function suggestions(Collection $records, string $search, string $newLabel): array
    {
        $search = trim($search);
        $normalized = Names::normalize($search);

        $matches = $records
            ->filter(fn ($record) => $normalized === '' || str_contains(Names::normalize($record->name), $normalized))
            ->take(50)
            ->mapWithKeys(fn ($record) => [$record->getKey() => $record->name])
            ->all();

        $exists = $records->contains(fn ($record) => Names::normalize($record->name) === $normalized);

        if ($search !== '' && ! $exists) {
            $matches[self::NEW.$search] = $search.' ('.$newLabel.')';
        }

        return $matches;
    }

    private static function isNew(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::NEW);
    }

    private static function typed(string $value): string
    {
        return trim(substr($value, strlen(self::NEW)));
    }
}
