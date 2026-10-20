<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_figures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('description')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('wikidata_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('affiliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('public_figure_id')->constrained()->cascadeOnDelete();
            $table->string('organization');
            $table->string('role')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->timestamps();

            $table->index(['public_figure_id', 'started_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliations');
        Schema::dropIfExists('public_figures');
    }
};
