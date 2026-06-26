<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('task_type', 128);
            $table->string('display_name', 255);
            $table->text('description')->nullable();
            $table->string('cron_expression', 128)->nullable();
            $table->timestampTz('run_at')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->json('payload')->nullable();
            $table->json('entity_reference')->nullable();
            $table->string('status', 32);
            $table->boolean('enabled')->default(true);
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampTz('next_run_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_membership_id')->references('id')->on('organization_memberships')->nullOnDelete();
            $table->index(['organization_id', 'status', 'enabled']);
            $table->index(['organization_id', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
