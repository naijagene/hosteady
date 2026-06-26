<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('scheduled_task_id');
            $table->uuid('platform_job_id')->nullable();
            $table->string('status', 32);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('output')->nullable();
            $table->timestampsTz();

            $table->foreign('scheduled_task_id')->references('id')->on('scheduled_tasks')->restrictOnDelete();
            $table->foreign('platform_job_id')->references('id')->on('platform_jobs')->nullOnDelete();
            $table->index(['scheduled_task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
