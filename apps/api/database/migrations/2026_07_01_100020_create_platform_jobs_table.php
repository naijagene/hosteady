<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('job_type', 128);
            $table->string('display_name', 255)->nullable();
            $table->string('queue_name', 64)->nullable();
            $table->string('status', 32);
            $table->string('priority', 32);
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_class', 255)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->uuid('correlation_id')->nullable();
            $table->json('entity_reference')->nullable();
            $table->uuid('scheduled_task_id')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('scheduled_task_id')->references('id')->on('scheduled_tasks')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_membership_id')->references('id')->on('organization_memberships')->nullOnDelete();
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_jobs');
    }
};
