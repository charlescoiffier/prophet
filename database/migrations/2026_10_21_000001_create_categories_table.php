<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * La catégorie d'une personnalité devient une table, que les modérateurs peuvent enrichir.
 * Les cinq catégories d'origine (ancienne liste figée) sont reprises, et les fiches existantes rattachées.
 */
return new class extends Migration
{
    /** Ancienne valeur => nom affiché. */
    private const DEFAULTS = [
        'politician' => 'Politique',
        'journalist' => 'Journaliste',
        'columnist' => 'Éditorialiste',
        'economist' => 'Économiste',
        'other' => 'Autre',
    ];

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        $ids = [];
        foreach (self::DEFAULTS as $old => $name) {
            $ids[$old] = (string) Str::uuid7();
            DB::table('categories')->insert([
                'id' => $ids[$old], 'name' => $name, 'slug' => Str::slug($name), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('public_figures', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->after('slug')->constrained()->restrictOnDelete();
        });

        foreach ($ids as $old => $id) {
            DB::table('public_figures')->where('category', $old)->update(['category_id' => $id]);
        }
        DB::table('public_figures')->whereNull('category_id')->update(['category_id' => $ids['other']]);

        Schema::table('public_figures', function (Blueprint $table) {
            $table->uuid('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('public_figures', function (Blueprint $table) {
            $table->string('category')->default('other');
        });

        $values = array_flip(array_map(fn (string $name) => Str::slug($name), self::DEFAULTS));
        foreach (DB::table('categories')->get() as $category) {
            DB::table('public_figures')->where('category_id', $category->id)->update(['category' => $values[$category->slug] ?? 'other']);
        }

        Schema::table('public_figures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('categories');
    }
};
