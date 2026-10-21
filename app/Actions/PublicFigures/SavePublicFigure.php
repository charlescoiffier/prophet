<?php

namespace App\Actions\PublicFigures;

use App\Actions\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\PublicFigure;
use App\Models\User;
use App\Support\Names;
use App\Support\Organizations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Crée ou corrige une fiche de personnalité et son historique d'affiliations.
 */
class SavePublicFigure
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Prénom et nom (ou, à défaut, un nom complet « name », coupé au premier espace).
     *
     * @param  array{first_name?: string|null, last_name?: string, name?: string, affiliations?: list<array<string, mixed>>}&array<string, mixed>  $data
     */
    public function handle(?PublicFigure $figure, User $actor, array $data): PublicFigure
    {
        Gate::forUser($actor)->authorize($figure === null ? 'create' : 'update', $figure ?? PublicFigure::class);
        $this->ensurePhotoIsComplete($data);

        return DB::transaction(function () use ($figure, $actor, $data) {
            $isNew = $figure === null;
            $figure ??= new PublicFigure;
            $before = $isNew ? [] : $this->snapshot($figure);

            $previousPhoto = $figure->photo_path;
            $previousOriginal = $figure->photo_original_path;

            $figure->fill($data);
            $displayName = filled($figure->last_name) ? Names::full($figure->first_name, $figure->last_name) : (string) $figure->name;
            $figure->slug = filled($data['slug'] ?? null) ? $data['slug'] : ($figure->slug ?? Str::slug($displayName));
            $figure->save();

            // Photo remplacée ou retirée : l'ancien fichier ne sert plus.
            if ($previousPhoto !== null && $previousPhoto !== $figure->photo_path) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($previousPhoto));
            }
            if ($previousOriginal !== null && $previousOriginal !== $figure->photo_original_path) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($previousOriginal));
            }

            if (array_key_exists('affiliations', $data)) {
                $this->syncAffiliations($figure, $data['affiliations']);
            }

            if ($isNew) {
                $this->recordActivity->handle($figure, $actor, ActivityAction::Proposed, after: $this->snapshot($figure));
            } else {
                [$changedBefore, $changedAfter] = RecordActivity::diff($before, $this->snapshot($figure));
                if ($changedAfter !== []) {
                    $this->recordActivity->handle($figure, $actor, ActivityAction::Edited, $changedBefore, $changedAfter);
                }
            }

            return $figure;
        });
    }

    /**
     * Une photo de l'atelier va avec son original et ses réglages ; une photo seule (téléversée
     * avant l'atelier) reste acceptée.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensurePhotoIsComplete(array $data): void
    {
        $fromStudio = filled($data['photo_original_path'] ?? null) || filled($data['photo_settings'] ?? null);
        $complete = filled($data['photo_path'] ?? null) && filled($data['photo_original_path'] ?? null) && filled($data['photo_settings'] ?? null);

        if ($fromStudio && ! $complete) {
            throw ValidationException::withMessages(['photo_path' => __('validation.custom.photo_path.incomplete')]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $affiliations
     */
    private function syncAffiliations(PublicFigure $figure, array $affiliations): void
    {
        $keep = [];

        foreach ($affiliations as $attributes) {
            // « parti a » devient « Parti A » si cette organisation est déjà connue : pas de doublon.
            $attributes['organization'] = filled($attributes['organization'] ?? null) ? Organizations::canonical($attributes['organization']) : null;

            $affiliation = $figure->affiliations()->updateOrCreate(
                ['id' => $attributes['id'] ?? null],
                collect($attributes)->except('id')->all(),
            );
            $keep[] = $affiliation->id;
        }

        $figure->affiliations()->whereNotIn('id', $keep)->delete();
        $figure->unsetRelation('affiliations');
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(PublicFigure $figure): array
    {
        return [
            'name' => $figure->name,
            'slug' => $figure->slug,
            'category' => $figure->category()->value('name'),
            'photo_url' => $figure->photo_url,
            'photo_path' => $figure->photo_path,
            'wikidata_id' => $figure->wikidata_id,
            'affiliations' => $figure->affiliations()->get()
                ->map(fn ($a) => trim("{$a->label()} ({$a->started_on?->toDateString()} → {$a->ended_on?->toDateString()})"))
                ->all(),
        ];
    }
}
