<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('scope', 32)->default('organization');
            $table->string('status', 32)->default('draft');
            $table->string('module_key', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('deleted_by_user_id')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('rule_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('rule_set_id');
            $table->uuid('rule_set_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 64)->default('validation');
            $table->string('scope', 32)->default('organization');
            $table->string('status', 32)->default('draft');
            $table->string('trigger_type', 64)->default('manual');
            $table->unsignedInteger('priority')->default(100);
            $table->json('conditions_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('entity_key', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('deleted_by_user_id')->nullable();

            $table->foreign('rule_set_id')->references('id')->on('rule_sets')->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'trigger_type', 'status']);
            $table->index(['rule_set_public_id', 'status']);
        });

        Schema::create('rule_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('rule_definition_id')->nullable();
            $table->uuid('rule_public_id')->nullable();
            $table->string('trigger_type', 64)->default('manual');
            $table->boolean('matched')->default(false);
            $table->boolean('allowed')->default(true);
            $table->json('violations_json')->nullable();
            $table->json('traces_json')->nullable();
            $table->json('facts_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('rule_definition_id')->references('id')->on('rule_definitions')->nullOnDelete();
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('rule_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('rule_definition_id')->nullable();
            $table->uuid('rule_public_id')->nullable();
            $table->string('trigger_type', 64)->default('manual');
            $table->string('status', 32)->default('completed');
            $table->json('matched_rules_json')->nullable();
            $table->json('actions_applied_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->json('violations_json')->nullable();
            $table->json('facts_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('rule_definition_id')->references('id')->on('rule_definitions')->nullOnDelete();
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('rule_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('rule_set_id')->nullable();
            $table->uuid('rule_definition_id')->nullable();
            $table->uuid('rule_public_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('rule_set_id')->references('id')->on('rule_sets')->nullOnDelete();
            $table->foreign('rule_definition_id')->references('id')->on('rule_definitions')->nullOnDelete();
            $table->index(['organization_id', 'rule_public_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_activity_logs');
        Schema::dropIfExists('rule_executions');
        Schema::dropIfExists('rule_evaluations');
        Schema::dropIfExists('rule_definitions');
        Schema::dropIfExists('rule_sets');
    }
};
