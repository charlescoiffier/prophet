<?php

namespace App\Actions\Sources;

use App\Jobs\ArchiveSource;
use App\Models\Card;
use App\Models\Source;
use App\Models\Verdict;
use Illuminate\Support\Collection;

/**
 * Seul point d'enregistrement des sources : chacune part à l'archivage (règle 7).
 */
class AttachSources
{
    /**
     * @param  list<array<string, mixed>>  $sources
     * @return Collection<int, Source>
     */
    public function handle(Card|Verdict $owner, array $sources): Collection
    {
        return collect($sources)->map(function (array $attributes) use ($owner) {
            $source = $owner->sources()->create($attributes);

            ArchiveSource::dispatch($source)->afterCommit();

            return $source;
        });
    }
}
