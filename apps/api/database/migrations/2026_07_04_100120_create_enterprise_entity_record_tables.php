<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_entity_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->json('record_data');
            $table->text('search_text')->nullable();
            $table->string('status', 32)->default('active');
            $table->string('visibility', 32)->default('organization');
            $table->unsignedInteger('version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['module_key', 'entity_key', 'status']);
            $table->index(['organization_id', 'module_key', 'entity_key', 'deleted_at']);
        });

        Schema::create('enterprise_entity_record_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_entity_record_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->uuid('record_public_id');
            $table->unsignedInteger('version_number');
            $table->json('record_data');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('enterprise_entity_record_id')->references('id')->on('enterprise_entity_records')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(['enterprise_entity_record_id', 'version_number'], 'enterprise_entity_record_versions_unique');
            $table->index(['organization_id', 'module_key', 'entity_key', 'record_public_id']);
        });

        Schema::create('enterprise_entity_record_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('source_module_key', 128);
            $table->string('source_entity_key', 128);
            $table->uuid('source_record_public_id');
            $table->string('target_module_key', 128);
            $table->string('target_entity_key', 128);
            $table->uuid('target_record_public_id');
            $table->string('relationship_key', 128);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'source_module_key', 'source_entity_key', 'source_record_public_id']);
            $table->index(['target_module_key', 'target_entity_key', 'target_record_public_id']);
        });

        Schema::create('enterprise_entity_record_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->uuid('record_public_id');
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key', 'record_public_id']);
            $table->index(['module_key', 'entity_key', 'record_public_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_entity_record_activity');
        Schema::dropIfExists('enterprise_entity_record_links');
        Schema::dropIfExists('enterprise_entity_record_versions');
        Schema::dropIfExists('enterprise_entity_records');
    }
};
