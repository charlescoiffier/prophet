<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveSource;
use App\Models\Source;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Filet de sécurité de la règle 7 : relance l'archivage des sources restées sans copie.
 */
#[Signature('sources:archive-missing')]
#[Description('Relance l’archivage Wayback Machine des sources sans copie archivée')]
class ArchiveMissingSources extends Command
{
    public function handle(): int
    {
        $count = 0;

        Source::whereNull('archive_url')
            ->where('created_at', '<', now()->subDay())
            ->lazyById()
            ->each(function (Source $source) use (&$count) {
                ArchiveSource::dispatch($source);
                $count++;
            });

        $this->info("{$count} source(s) remise(s) en file d’archivage.");

        return self::SUCCESS;
    }
}
