<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->string('dashboard_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->string('visibility', 32);
            $table->json('layout_json')->nullable();
            $table->json('filters_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(
                ['organization_id', 'workspace_id', 'module_key', 'dashboard_key'],
                'dashboard_definitions_org_workspace_module_dashboard_unique',
            );
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('dashboard_definition_id');
            $table->string('widget_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('widget_type', 64);
            $table->string('chart_type', 32)->nullable();
            $table->string('data_source_type', 64)->nullable();
            $table->json('data_source_config')->nullable();
            $table->json('metric_json')->nullable();
            $table->json('filters_json')->nullable();
            $table->json('layout_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->string('refresh_mode', 32)->default('on_load');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('dashboard_definition_id')->references('id')->on('dashboard_definitions')->restrictOnDelete();
            $table->index(['dashboard_definition_id', 'sort_order']);
            $table->unique(['dashboard_definition_id', 'widget_key'], 'dashboard_widgets_definition_widget_unique');
        });

        Schema::create('dashboard_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('dashboard_definition_id');
            $table->string('name', 255);
            $table->json('layout_json')->nullable();
            $table->json('filters_json')->nullable();
            $table->boolean('is_default')->default(false);
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('dashboard_definition_id')->references('id')->on('dashboard_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'dashboard_definition_id']);
        });

        Schema::create('dashboard_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('dashboard_definition_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('dashboard_definition_id')->references('id')->on('dashboard_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'dashboard_definition_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_activity_logs');
        Schema::dropIfExists('dashboard_views');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_definitions');
    }
};
