<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('category_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'module_key', 'category_key'], 'workflow_categories_key_unique');
            $table->index(['organization_id', 'workspace_id']);
        });

        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('workflow_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->uuid('current_version_id')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('workflow_categories')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'module_key', 'workflow_key'], 'workflow_definitions_key_unique');
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_definition_id');
            $table->unsignedInteger('version_number');
            $table->string('status', 32)->default('draft');
            $table->json('definition_json');
            $table->json('validation_report')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();

            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->unique(['workflow_definition_id', 'version_number'], 'workflow_versions_number_unique');
            $table->index(['workflow_definition_id', 'status']);
        });

        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('workflow_versions')->nullOnDelete();
        });

        Schema::create('workflow_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_definition_id');
            $table->string('variable_key', 128);
            $table->string('label', 255);
            $table->string('type', 64);
            $table->json('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->unique(['workflow_definition_id', 'variable_key'], 'workflow_variables_key_unique');
        });

        Schema::create('workflow_definition_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_definition_id');
            $table->uuid('workflow_version_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->foreign('workflow_version_id')->references('id')->on('workflow_versions')->nullOnDelete();
            $table->index(['workflow_definition_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('workflow_definition_history');
        Schema::dropIfExists('workflow_variables');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('workflow_categories');
    }
};
