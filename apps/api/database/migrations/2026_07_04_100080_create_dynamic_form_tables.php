<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->string('form_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->string('visibility', 32);
            $table->json('layout_json');
            $table->json('sections_json');
            $table->json('fields_json');
            $table->json('actions_json');
            $table->json('conditions_json');
            $table->json('validation_rules_json');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(
                ['organization_id', 'workspace_id', 'module_key', 'form_key'],
                'form_definitions_org_workspace_module_form_unique',
            );
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('form_definition_id');
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->uuid('entity_public_id')->nullable();
            $table->string('status', 32);
            $table->json('submission_data');
            $table->json('validation_report')->nullable();
            $table->uuid('submitted_by_user_id')->nullable();
            $table->uuid('submitted_membership_id')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('form_definition_id')->references('id')->on('form_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'form_definition_id']);
            $table->index(['module_key', 'entity_key', 'entity_public_id']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('form_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('form_definition_id');
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->uuid('entity_public_id')->nullable();
            $table->json('draft_data');
            $table->timestampTz('expires_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('form_definition_id')->references('id')->on('form_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'form_definition_id']);
            $table->index(['module_key', 'entity_key', 'entity_public_id']);
            $table->index(['expires_at']);
        });

        Schema::create('form_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('form_definition_id')->nullable();
            $table->uuid('form_submission_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('form_definition_id')->references('id')->on('form_definitions')->nullOnDelete();
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'form_definition_id']);
            $table->index(['form_submission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_activity_logs');
        Schema::dropIfExists('form_drafts');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_definitions');
    }
};
