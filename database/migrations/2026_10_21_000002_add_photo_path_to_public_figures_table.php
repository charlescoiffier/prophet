<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo téléversée depuis la modération (disque « public »), en plus du lien externe photo_url.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('photo_url');
        });
    }

    public function down(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
