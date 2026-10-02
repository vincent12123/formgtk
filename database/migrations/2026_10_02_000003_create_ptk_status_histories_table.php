<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat status pengajuan (SPEC.md §4.5) — hanya ditambah, tidak pernah diubah/dihapus.
     */
    public function up(): void
    {
        Schema::create('ptk_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ptk_submission_id')->constrained()->cascadeOnDelete();
            $table->string('actor');
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptk_status_histories');
    }
};
