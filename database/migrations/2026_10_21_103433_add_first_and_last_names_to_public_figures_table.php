<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prénom et nom d'une personnalité, saisis séparément ; « name » reste le nom affiché (« Prénom Nom »),
 * recomposé à l'enregistrement. Les fiches existantes sont coupées au premier espace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 155)->nullable()->after('first_name');
        });

        DB::statement(<<<'SQL'
            update public_figures set
                first_name = case when position(' ' in trim(name)) > 0 then split_part(trim(name), ' ', 1) end,
                last_name = case when position(' ' in trim(name)) > 0 then trim(substr(trim(name), position(' ' in trim(name)) + 1)) else trim(name) end
            SQL);
    }

    public function down(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
