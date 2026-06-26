<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_automation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_definition_id');
            $table->string('trigger_type', 32);
            $table->json('trigger_config')->nullable();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
            $table->index(['organization_id', 'trigger_type', 'status']);
        });

        Schema::create('workflow_trigger_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_automation_rule_id');
            $table->string('event_name', 128);
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_automation_rule_id')->references('id')->on('workflow_automation_rules')->restrictOnDelete();
            $table->index(['event_name', 'status']);
            $table->index(['organization_id', 'event_name', 'status']);
        });

        Schema::create('workflow_trigger_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_automation_rule_id');
            $table->string('trigger_source', 32);
            $table->string('status', 32)->default('pending');
            $table->uuid('workflow_instance_id')->nullable();
            $table->string('event_name', 128)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('executed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_automation_rule_id')->references('id')->on('workflow_automation_rules')->restrictOnDelete();
            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->nullOnDelete();
            $table->index(['organization_id', 'status', 'executed_at']);
            $table->index(['workflow_automation_rule_id', 'executed_at']);
        });

        Schema::create('workflow_timers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_instance_id');
            $table->string('node_id', 128);
            $table->string('timer_type', 32);
            $table->string('status', 32)->default('active');
            $table->timestampTz('due_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'due_at']);
            $table->index(['workflow_instance_id', 'node_id']);
        });

        Schema::create('workflow_timer_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_timer_id');
            $table->string('status', 32)->default('pending');
            $table->timestampTz('executed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('workflow_timer_id')->references('id')->on('workflow_timers')->restrictOnDelete();
            $table->index(['workflow_timer_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_timer_executions');
        Schema::dropIfExists('workflow_timers');
        Schema::dropIfExists('workflow_trigger_executions');
        Schema::dropIfExists('workflow_trigger_subscriptions');
        Schema::dropIfExists('workflow_automation_rules');
    }
};
