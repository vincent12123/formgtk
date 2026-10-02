<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unit sekolah yayasan — data tetap (SPEC.md §4.2), NPSN sebagai kunci.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->string('npsn', 8)->primary();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
