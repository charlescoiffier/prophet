<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * « Fonction actuelle » disparaît : seules restent les fonctions successives (fonction, organisation,
 * début, fin). L'organisation devient facultative (« Économiste »). Une fonction actuelle saisie sans
 * fonction en cours qui lui corresponde devient une fonction commencée aujourd'hui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliations', function (Blueprint $table) {
            $table->string('organization')->nullable()->change();
        });

        DB::statement('alter table affiliations add constraint affiliations_role_or_organization check (role is not null or organization is not null)');

        $today = now()->toDateString();
        $figures = DB::table('public_figures')->whereNotNull('description')->where('description', '<>', '')->get(['id', 'description']);

        foreach ($figures as $figure) {
            $hasCurrent = DB::table('affiliations')
                ->where('public_figure_id', $figure->id)
                ->where(fn ($query) => $query->whereNull('started_on')->orWhere('started_on', '<=', $today))
                ->where(fn ($query) => $query->whereNull('ended_on')->orWhere('ended_on', '>=', $today))
                ->exists();

            if (! $hasCurrent) {
                DB::table('affiliations')->insert([
                    'id' => (string) Str::uuid7(),
                    'public_figure_id' => $figure->id,
                    'role' => $figure->description,
                    'organization' => null,
                    'started_on' => $today,
                    'ended_on' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('public_figures', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->string('description')->nullable();
        });

        DB::statement('alter table affiliations drop constraint affiliations_role_or_organization');
    }
};
