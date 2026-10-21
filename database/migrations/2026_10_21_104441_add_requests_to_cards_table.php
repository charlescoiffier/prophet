<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Personnalité et thèmes demandés par l'auteur d'une proposition quand ils n'existent pas encore :
 * un modérateur crée la fiche et les thèmes, puis les rattache avant de publier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('requested_figure', 255)->nullable()->after('public_figure_id');
            $table->string('requested_themes', 500)->nullable()->after('requested_figure');
            $table->uuid('public_figure_id')->nullable()->change();
        });

        // Une carte a une personnalité, ou en demande une ; elle n'est publiée qu'avec sa personnalité.
        DB::statement('alter table cards add constraint cards_figure_or_request check (public_figure_id is not null or requested_figure is not null)');
        DB::statement("alter table cards add constraint cards_public_has_figure check (public_figure_id is not null or status in ('awaiting_email', 'in_moderation', 'rejected'))");
    }

    public function down(): void
    {
        DB::statement('alter table cards drop constraint cards_public_has_figure');
        DB::statement('alter table cards drop constraint cards_figure_or_request');

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['requested_figure', 'requested_themes']);
            $table->uuid('public_figure_id')->nullable(false)->change();
        });
    }
};
