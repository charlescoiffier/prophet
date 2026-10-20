<?php

use App\Jobs\ArchiveSource;
use App\Models\Source;
use Illuminate\Support\Facades\Http;

function snapshotResponse(string $timestamp): array
{
    return ['archived_snapshots' => ['closest' => [
        'available' => true,
        'status' => '200',
        'timestamp' => $timestamp,
        'url' => "http://web.archive.org/web/{$timestamp}/https://example.org/article",
    ]]];
}

beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

it('demande la capture puis enregistre la copie archivée', function () {
    Http::fake([
        'web.archive.org/save' => Http::response('', 200),
        'archive.org/wayback/available*' => Http::response(snapshotResponse('20261020120500')),
    ]);
    $source = Source::factory()->create(['url' => 'https://example.org/article']);

    ArchiveSource::dispatchSync($source);

    expect($source->refresh())
        ->archive_url->toBe('http://web.archive.org/web/20261020120500/https://example.org/article')
        ->archived_at->toDateTimeString()->toBe('2026-10-20 12:05:00');
    Http::assertSent(fn ($request) => $request->url() === 'https://web.archive.org/save' && $request['url'] === 'https://example.org/article');
});

it('n’accepte pas une capture ancienne, antérieure à l’enregistrement de la source', function () {
    Http::fake([
        'web.archive.org/save' => Http::response('', 200),
        'archive.org/wayback/available*' => Http::response(snapshotResponse('20190101000000')),
    ]);
    $source = Source::factory()->create();

    $job = (new ArchiveSource($source))->withFakeQueueInteractions();
    $job->handle();

    $job->assertReleased(ArchiveSource::RECHECK_DELAY);
    expect($source->refresh()->archive_url)->toBeNull();
});

it('se replace en file quand la capture n’est pas encore disponible', function () {
    Http::fake([
        'web.archive.org/save' => Http::response('', 200),
        'archive.org/wayback/available*' => Http::response(['archived_snapshots' => []]),
    ]);
    $source = Source::factory()->create();

    $job = (new ArchiveSource($source))->withFakeQueueInteractions();
    $job->handle();

    $job->assertReleased(ArchiveSource::RECHECK_DELAY);
});

it('patiente quand archive.org limite les appels (429)', function () {
    Http::fake([
        'web.archive.org/save' => Http::response('', 429),
        'archive.org/wayback/available*' => Http::response('Too Many Requests', 429),
    ]);
    $source = Source::factory()->create();

    $job = (new ArchiveSource($source))->withFakeQueueInteractions();
    $job->handle();

    $job->assertReleased(ArchiveSource::RECHECK_DELAY);
    $job->assertNotFailed();
});

it('ne refait rien pour une source déjà archivée', function () {
    Http::fake();
    $source = Source::factory()->archived()->create();

    ArchiveSource::dispatchSync($source);

    Http::assertNothingSent();
});
