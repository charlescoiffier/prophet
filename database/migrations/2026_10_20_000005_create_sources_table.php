<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('verdict_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->text('archive_url')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('kind');
            $table->string('outlet')->nullable();
            $table->date('published_on')->nullable();
            $table->string('timecode')->nullable();
            $table->timestamps();
        });

        // Une source étaye soit une carte, soit un verdict.
        DB::statement('ALTER TABLE sources ADD CONSTRAINT sources_single_owner CHECK (num_nonnulls(card_id, verdict_id) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
