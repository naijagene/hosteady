<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('key', 128)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('version', 32);
            $table->string('status', 32)->default('active');
            $table->boolean('is_core')->default(false);
            $table->string('icon', 255)->nullable();
            $table->string('category', 64)->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
            $table->index('is_core');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
