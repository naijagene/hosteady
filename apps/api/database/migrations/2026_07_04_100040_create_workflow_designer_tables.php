<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_canvas_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_definition_id');
            $table->uuid('workflow_version_id')->nullable();
            $table->json('canvas_json');
            $table->string('status', 32)->default('saved');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
            $table->foreign('workflow_version_id')->references('id')->on('workflow_versions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'workflow_definition_id']);
            $table->index(['workflow_definition_id', 'created_at']);
        });

        Schema::create('workflow_node_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('node_type', 32);
            $table->string('label', 255);
            $table->string('category', 64)->nullable();
            $table->float('default_width')->default(120);
            $table->float('default_height')->default(60);
            $table->json('default_config')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'node_type']);
            $table->index(['is_system', 'node_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_node_templates');
        Schema::dropIfExists('workflow_canvas_snapshots');
    }
};
