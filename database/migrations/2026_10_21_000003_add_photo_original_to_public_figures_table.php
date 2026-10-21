<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atelier portrait : photo d'origine (disque « public ») et réglages qui ont produit photo_path,
 * pour pouvoir retoucher la découpe ou les tons plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->string('photo_original_path')->nullable()->after('photo_path');
            $table->jsonb('photo_settings')->nullable()->after('photo_original_path');
        });
    }

    public function down(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->dropColumn(['photo_original_path', 'photo_settings']);
        });
    }
};
