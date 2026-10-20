<?php

namespace App\Jobs;

use App\Models\Source;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Archive une source sur la Wayback Machine (« Save Page Now »), règle 7.
 *
 * Deux temps : on demande la capture, puis on attend qu'elle apparaisse dans
 * l'API de disponibilité. Tant qu'elle n'y est pas, le job se replace en file.
 */
class ArchiveSource implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** Délai avant de revérifier qu'une capture demandée est disponible. */
    public const RECHECK_DELAY = 300;

    public int $timeout = 150;

    public int $uniqueFor = 3600;

    public function __construct(public Source $source) {}

    public function uniqueId(): string
    {
        return $this->source->getKey();
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('wayback')];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        if ($this->source->isArchived()) {
            return;
        }

        if ($this->attempts() === 1) {
            $this->requestCapture();
        }

        $response = $this->client()
            ->timeout(20)
            ->get('https://archive.org/wayback/available', [
                'url' => $this->source->url,
                'timestamp' => now()->format('YmdHis'),
            ]);

        // archive.org limite vite les appels (429) : on attend au lieu de forcer.
        if ($response->status() === 429 || $response->serverError()) {
            $this->release(self::RECHECK_DELAY);

            return;
        }

        $snapshot = $this->recentSnapshot($response->throw()->json('archived_snapshots.closest'));

        if ($snapshot === null) {
            $this->release(self::RECHECK_DELAY);

            return;
        }

        $this->source->forceFill([
            'archive_url' => $snapshot['url'],
            'archived_at' => Carbon::createFromFormat('YmdHis', $snapshot['timestamp']),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Archivage de la source impossible', [
            'source_id' => $this->source->getKey(),
            'url' => $this->source->url,
            'exception' => $exception?->getMessage(),
        ]);
    }

    /**
     * Demande la capture. Une erreur ici n'est pas fatale : la page a parfois déjà été archivée.
     */
    private function requestCapture(): void
    {
        try {
            $this->client()
                ->timeout(120)
                ->asForm()
                ->post('https://web.archive.org/save', ['url' => $this->source->url]);
        } catch (ConnectionException) {
            // La vérification qui suit, puis les nouvelles tentatives, prennent le relais.
        }
    }

    /**
     * Garde la capture la plus proche si elle date de l'enregistrement de la source.
     *
     * @return array{url: string, timestamp: string}|null
     */
    private function recentSnapshot(mixed $closest): ?array
    {
        if (! is_array($closest) || ($closest['available'] ?? false) !== true) {
            return null;
        }

        $capturedAt = Carbon::createFromFormat('YmdHis', $closest['timestamp']);

        // Une ancienne capture ne prouve pas ce que la page disait au moment de la déclaration.
        if ($capturedAt->lt($this->source->created_at->copy()->subDay())) {
            return null;
        }

        return ['url' => $closest['url'], 'timestamp' => $closest['timestamp']];
    }

    private function client(): PendingRequest
    {
        $request = Http::connectTimeout(10)
            ->withUserAgent(config('app.name').' (archivage des sources)')
            ->acceptJson();

        $key = config('services.wayback.access_key');
        $secret = config('services.wayback.secret_key');

        return $key && $secret
            ? $request->withHeaders(['Authorization' => "LOW {$key}:{$secret}"])
            : $request;
    }
}
