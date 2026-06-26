<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_search_indexes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('entity_type', 128);
            $table->string('entity_public_id', 128);
            $table->json('entity_reference')->nullable();
            $table->string('display_name', 255);
            $table->text('keywords')->nullable();
            $table->json('metadata')->nullable();
            $table->string('visibility', 32)->default('organization');
            $table->text('search_vector')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(['organization_id', 'module_key', 'entity_type', 'entity_public_id'], 'platform_search_indexes_entity_unique');
            $table->index(['organization_id', 'workspace_id']);
            $table->index(['organization_id', 'module_key']);
        });

        Schema::create('platform_saved_searches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('membership_id');
            $table->string('name', 255);
            $table->string('query', 512)->nullable();
            $table->json('filters')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'membership_id']);
        });

        Schema::create('search_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('membership_id');
            $table->string('query', 512)->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->json('filters')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'membership_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_activity_logs');
        Schema::dropIfExists('platform_saved_searches');
        Schema::dropIfExists('platform_search_indexes');
    }
};
