<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->string('table_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->string('visibility', 32);
            $table->json('columns_json');
            $table->json('filters_json')->nullable();
            $table->json('sorts_json')->nullable();
            $table->json('default_sort_json')->nullable();
            $table->json('pagination_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('views_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(
                ['organization_id', 'workspace_id', 'module_key', 'table_key'],
                'table_definitions_org_workspace_module_table_unique',
            );
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('table_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('table_definition_id');
            $table->string('module_key', 128);
            $table->string('table_key', 128);
            $table->string('name', 255);
            $table->json('columns_json');
            $table->json('filters_json')->nullable();
            $table->json('sorts_json')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('table_definition_id')->references('id')->on('table_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'table_definition_id']);
            $table->index(['module_key', 'table_key']);
        });

        Schema::create('table_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('table_definition_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('table_definition_id')->references('id')->on('table_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'table_definition_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_activity_logs');
        Schema::dropIfExists('table_views');
        Schema::dropIfExists('table_definitions');
    }
};
