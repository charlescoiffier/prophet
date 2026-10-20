<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verdicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->text('justification');
            $table->foreignUuid('proposed_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('proposed');
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Règle 4 : personne ne décide sur sa propre proposition.
        DB::statement('ALTER TABLE verdicts ADD CONSTRAINT verdicts_not_self_decided CHECK (decided_by IS NULL OR decided_by <> proposed_by)');

        // Règle 5 : une seule proposition de verdict en attente par carte.
        DB::statement("CREATE UNIQUE INDEX verdicts_one_pending_per_card ON verdicts (card_id) WHERE status = 'proposed'");

        // Au plus un verdict validé par carte.
        DB::statement("CREATE UNIQUE INDEX verdicts_one_approved_per_card ON verdicts (card_id) WHERE status = 'approved'");
    }

    public function down(): void
    {
        Schema::dropIfExists('verdicts');
    }
};
