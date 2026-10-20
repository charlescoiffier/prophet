<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('public_figure_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->text('quote');
            $table->text('context')->nullable();
            $table->date('stated_on');
            $table->text('testable_statement')->nullable();
            $table->text('verdict_criterion')->nullable();
            $table->date('deadline')->nullable();
            $table->string('deadline_nature')->nullable();
            $table->string('status')->default('in_moderation');
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_email')->nullable();
            $table->timestamp('email_confirmed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline']);
            $table->index('author_email');
        });

        Schema::create('card_theme', function (Blueprint $table) {
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('theme_id')->constrained()->cascadeOnDelete();
            $table->primary(['card_id', 'theme_id']);
        });

        // Une carte a toujours un auteur : un membre ou, à défaut, une adresse e-mail.
        DB::statement('ALTER TABLE cards ADD CONSTRAINT cards_author_present CHECK (author_id IS NOT NULL OR author_email IS NOT NULL)');

        // Recherche plein texte en français sur la citation et son contexte.
        DB::statement("CREATE INDEX cards_search_fr ON cards USING gin (to_tsvector('french', quote || ' ' || coalesce(context, '')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('card_theme');
        Schema::dropIfExists('cards');
    }
};
