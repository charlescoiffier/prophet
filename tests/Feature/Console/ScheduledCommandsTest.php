<?php

use App\Jobs\ArchiveSource;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\Source;
use Illuminate\Support\Facades\Queue;

it('efface les cartes non confirmées après le délai du lien, avec leur journal', function () {
    $old = Card::factory()->anonymous()->create(['created_at' => now()->subDays(9)]);
    ActivityLog::factory()->create(['subject_id' => $old->id]);
    $recent = Card::factory()->anonymous()->create(['created_at' => now()->subDays(2)]);
    $confirmedOld = Card::factory()->create(['created_at' => now()->subDays(30)]);

    $this->artisan('cards:purge-unconfirmed')->assertSuccessful();

    expect(Card::pluck('id')->sort()->values()->all())->toBe(collect([$recent->id, $confirmedOld->id])->sort()->values()->all())
        ->and(ActivityLog::where('subject_id', $old->id)->exists())->toBeFalse();
});

it('relance l’archivage des sources restées sans copie', function () {
    Queue::fake();
    $missing = Source::factory()->create(['created_at' => now()->subDays(2)]);
    Source::factory()->archived()->create(['created_at' => now()->subDays(2)]);
    Source::factory()->create();

    $this->artisan('sources:archive-missing')->assertSuccessful();

    Queue::assertPushed(ArchiveSource::class, 1);
    Queue::assertPushed(ArchiveSource::class, fn (ArchiveSource $job) => $job->source->is($missing));
});
