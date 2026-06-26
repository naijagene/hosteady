<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_definition_id');
            $table->uuid('workflow_version_id');
            $table->string('status', 32)->default('pending');
            $table->string('current_node_id', 128)->nullable();
            $table->json('input_payload')->nullable();
            $table->json('result')->nullable();
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->foreign('workflow_version_id')->references('id')->on('workflow_versions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('workflow_execution_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_instance_id');
            $table->string('node_id', 128);
            $table->string('node_type', 64);
            $table->string('status', 32);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('result')->nullable();
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->index(['workflow_instance_id', 'started_at']);
        });

        Schema::create('workflow_execution_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_instance_id');
            $table->string('variable_key', 128);
            $table->json('value')->nullable();
            $table->string('source', 64);
            $table->timestampTz('snapshot_at');

            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->unique(['workflow_instance_id', 'variable_key'], 'workflow_execution_variables_unique');
        });

        Schema::create('workflow_execution_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_instance_id');
            $table->string('event_type', 128);
            $table->json('payload')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->index(['workflow_instance_id', 'created_at']);
        });

        Schema::create('workflow_execution_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_instance_id');
            $table->uuid('workflow_execution_step_id')->nullable();
            $table->string('level', 16);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->foreign('workflow_execution_step_id')->references('id')->on('workflow_execution_steps')->nullOnDelete();
            $table->index(['workflow_instance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_execution_logs');
        Schema::dropIfExists('workflow_execution_events');
        Schema::dropIfExists('workflow_execution_variables');
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_instances');
    }
};
