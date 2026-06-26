<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_human_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_instance_id');
            $table->string('node_id', 128);
            $table->string('task_type', 32);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('created');
            $table->string('priority', 16)->default('normal');
            $table->string('approval_status', 32)->nullable();
            $table->uuid('assignee_user_id')->nullable();
            $table->uuid('assignee_membership_id')->nullable();
            $table->string('assignee_role_key', 64)->nullable();
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampTz('opened_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->uuid('completed_by_user_id')->nullable();
            $table->uuid('completed_by_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
            $table->index(['workflow_instance_id', 'node_id']);
        });

        Schema::create('workflow_task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_human_task_id');
            $table->string('assignment_type', 64);
            $table->uuid('assignee_user_id')->nullable();
            $table->uuid('assignee_membership_id')->nullable();
            $table->string('role_key', 64)->nullable();
            $table->uuid('assigned_by_user_id')->nullable();
            $table->uuid('assigned_by_membership_id')->nullable();
            $table->timestampTz('assigned_at');
            $table->json('metadata')->nullable();

            $table->foreign('workflow_human_task_id')->references('id')->on('workflow_human_tasks')->restrictOnDelete();
            $table->index(['workflow_human_task_id', 'assigned_at']);
        });

        Schema::create('workflow_task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_human_task_id');
            $table->text('body');
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('workflow_human_task_id')->references('id')->on('workflow_human_tasks')->restrictOnDelete();
            $table->index(['workflow_human_task_id', 'created_at']);
        });

        Schema::create('workflow_task_escalations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_human_task_id');
            $table->string('escalation_rule', 128);
            $table->uuid('escalated_user_id')->nullable();
            $table->uuid('escalated_membership_id')->nullable();
            $table->timestampTz('escalated_at');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('workflow_human_task_id')->references('id')->on('workflow_human_tasks')->restrictOnDelete();
            $table->index(['workflow_human_task_id', 'escalated_at']);
        });

        Schema::create('workflow_approval_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_human_task_id');
            $table->string('decision_type', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->uuid('decided_by_user_id')->nullable();
            $table->uuid('decided_by_membership_id')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('workflow_human_task_id')->references('id')->on('workflow_human_tasks')->restrictOnDelete();
            $table->index(['workflow_human_task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approval_decisions');
        Schema::dropIfExists('workflow_task_escalations');
        Schema::dropIfExists('workflow_task_comments');
        Schema::dropIfExists('workflow_task_assignments');
        Schema::dropIfExists('workflow_human_tasks');
    }
};
