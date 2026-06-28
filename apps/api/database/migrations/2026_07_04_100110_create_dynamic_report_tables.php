<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('entity_key', 128)->nullable();
            $table->string('table_key', 128)->nullable();
            $table->string('dashboard_key', 128)->nullable();
            $table->string('report_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->string('visibility', 32);
            $table->json('columns_json')->nullable();
            $table->json('filters_json')->nullable();
            $table->json('sorts_json')->nullable();
            $table->json('groups_json')->nullable();
            $table->json('aggregates_json')->nullable();
            $table->json('metrics_json')->nullable();
            $table->json('charts_json')->nullable();
            $table->json('layout_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(
                ['organization_id', 'workspace_id', 'module_key', 'report_key'],
                'report_definitions_org_workspace_module_report_unique',
            );
            $table->index(['organization_id', 'workspace_id', 'module_key', 'entity_key']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('report_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 128);
            $table->string('template_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('layout_json')->nullable();
            $table->json('definition_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(
                ['organization_id', 'workspace_id', 'module_key', 'template_key'],
                'report_templates_org_workspace_module_template_unique',
            );
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('report_definition_id');
            $table->string('status', 32);
            $table->json('parameters_json')->nullable();
            $table->json('result_json')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'report_definition_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('report_definition_id');
            $table->uuid('report_run_id')->nullable();
            $table->string('export_format', 32);
            $table->string('status', 32);
            $table->json('file_reference')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->restrictOnDelete();
            $table->foreign('report_run_id')->references('id')->on('report_runs')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'report_definition_id']);
            $table->index(['status', 'export_format']);
        });

        Schema::create('report_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('report_definition_id');
            $table->string('name', 255);
            $table->string('status', 32);
            $table->string('cron_expression', 128)->nullable();
            $table->timestampTz('run_at')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->json('export_formats_json')->nullable();
            $table->json('recipients_json')->nullable();
            $table->json('parameters_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'report_definition_id']);
            $table->index(['status']);
        });

        Schema::create('report_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('report_definition_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'report_definition_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_activity_logs');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_definitions');
    }
};
