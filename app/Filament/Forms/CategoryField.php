<?php

namespace App\Filament\Forms;

use App\Actions\Categories\CreateCategory;
use App\Models\Category;
use App\Models\User;
use App\Support\Names;
use Filament\Forms\Components\Select;

/**
 * Catégorie d'une personnalité : on la choisit, ou on tape un nom qui n'existe pas encore,
 * comme pour une organisation. La nouvelle catégorie n'est créée qu'à l'enregistrement de la fiche.
 */
class CategoryField
{
    /** Préfixe d'une catégorie tapée qui n'existe pas encore. */
    private const NEW = 'nouvelle:';

    public static function make(string $name = 'category_id'): Select
    {
        return Select::make($name)
            ->label('Catégorie')
            ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
            ->searchable()
            ->required()
            ->getSearchResultsUsing(fn (string $search) => self::suggestions($search))
            ->getOptionLabelUsing(fn (?string $value) => str_starts_with((string) $value, self::NEW)
                ? substr($value, strlen(self::NEW)).' (nouvelle catégorie)'
                : Category::find($value)?->name);
    }

    /**
     * Remplace une catégorie tapée par son identifiant, en la créant si besoin (CreateCategory).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolve(array $data, User $actor, string $name = 'category_id'): array
    {
        $value = (string) ($data[$name] ?? '');

        if (str_starts_with($value, self::NEW)) {
            $data[$name] = app(CreateCategory::class)->handle($actor, substr($value, strlen(self::NEW)))->getKey();
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private static function suggestions(string $search): array
    {
        $search = trim($search);
        $normalized = Names::normalize($search);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $matches = $categories
            ->filter(fn (Category $category) => $normalized === '' || str_contains(Names::normalize($category->name), $normalized))
            ->mapWithKeys(fn (Category $category) => [$category->getKey() => $category->name])
            ->all();

        $exists = $categories->contains(fn (Category $category) => Names::normalize($category->name) === $normalized);

        if ($search !== '' && ! $exists) {
            $matches[self::NEW.$search] = $search.' (nouvelle catégorie)';
        }

        return $matches;
    }
}
