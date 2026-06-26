<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('event_name', 128);
            $table->json('payload')->nullable();
            $table->json('subject_reference')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('status', 32);
            $table->text('error_message')->nullable();
            $table->timestampTz('dispatched_at');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'dispatched_at']);
            $table->index(['event_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_events');
    }
};
