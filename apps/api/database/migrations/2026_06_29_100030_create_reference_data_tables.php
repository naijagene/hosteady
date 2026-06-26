<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_catalogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('key', 128)->unique();
            $table->string('name', 255);
            $table->unsignedInteger('version')->default(1);
            $table->string('module_key', 64)->nullable();
            $table->text('description')->nullable();
            $table->timestampsTz();
        });

        Schema::create('reference_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('reference_catalog_id');
            $table->string('code', 64);
            $table->string('label', 255);
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->foreign('reference_catalog_id')->references('id')->on('reference_catalogs')->cascadeOnDelete();
            $table->unique(['reference_catalog_id', 'code']);
            $table->index(['reference_catalog_id', 'active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_items');
        Schema::dropIfExists('reference_catalogs');
    }
};
