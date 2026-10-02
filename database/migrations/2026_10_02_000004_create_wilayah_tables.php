<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data wilayah lokal (SPEC.md §4.4): provinsi → kabupaten/kota → kecamatan → desa/kelurahan.
     * Sumber: CSV emsifa/api-wilayah-indonesia (v1) di database/data/.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('name');
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('parent_id', 2)->index();
            $table->foreign('parent_id')->references('id')->on('provinces')->cascadeOnDelete();
            $table->string('name');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->string('id', 7)->primary();
            $table->string('parent_id', 4)->index();
            $table->foreign('parent_id')->references('id')->on('regencies')->cascadeOnDelete();
            $table->string('name');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('parent_id', 7)->index();
            $table->foreign('parent_id')->references('id')->on('districts')->cascadeOnDelete();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
