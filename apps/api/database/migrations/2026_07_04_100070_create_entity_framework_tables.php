<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 128)->nullable();
            $table->string('status', 32)->default('registered');
            $table->string('visibility', 32)->default('organization');
            $table->string('ownership_scope', 32)->default('organization');
            $table->string('table_name', 255)->nullable();
            $table->string('class_name', 255)->nullable();
            $table->json('capabilities')->nullable();
            $table->json('fields')->nullable();
            $table->json('relationships')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('registered_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['module_key', 'entity_key'], 'entity_definitions_module_entity_unique');
            $table->index(['status', 'visibility']);
        });

        Schema::create('entity_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('source_entity_definition_id');
            $table->uuid('target_entity_definition_id')->nullable();
            $table->string('source_module_key', 128);
            $table->string('source_entity_key', 128);
            $table->string('target_module_key', 128)->nullable();
            $table->string('target_entity_key', 128)->nullable();
            $table->string('relationship_key', 128);
            $table->string('relationship_type', 32);
            $table->string('label', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('source_entity_definition_id')->references('id')->on('entity_definitions')->restrictOnDelete();
            $table->foreign('target_entity_definition_id')->references('id')->on('entity_definitions')->nullOnDelete();
            $table->index(['source_entity_definition_id', 'relationship_key']);
        });

        Schema::create('entity_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->uuid('entity_public_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['module_key', 'entity_key', 'entity_public_id', 'created_at']);
        });

        Schema::create('entity_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->uuid('entity_public_id');
            $table->text('comment_body');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key', 'entity_public_id']);
        });

        Schema::create('entity_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('tag_key', 128);
            $table->string('name', 255);
            $table->string('color', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'tag_key'], 'entity_tags_org_workspace_key_unique');
        });

        Schema::create('entity_taggables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('entity_tag_id');
            $table->string('module_key', 128);
            $table->string('entity_key', 128);
            $table->uuid('entity_public_id');
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('entity_tag_id')->references('id')->on('entity_tags')->restrictOnDelete();
            $table->unique(
                ['entity_tag_id', 'module_key', 'entity_key', 'entity_public_id'],
                'entity_taggables_tag_entity_unique',
            );
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key', 'entity_public_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_taggables');
        Schema::dropIfExists('entity_tags');
        Schema::dropIfExists('entity_comments');
        Schema::dropIfExists('entity_activity_logs');
        Schema::dropIfExists('entity_relationships');
        Schema::dropIfExists('entity_definitions');
    }
};
