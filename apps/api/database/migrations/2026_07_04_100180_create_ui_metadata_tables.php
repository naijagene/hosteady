<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('page_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('page_type', 64)->default('custom');
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('workspace');
            $table->string('route_path', 512)->nullable();
            $table->string('icon', 128)->nullable();
            $table->json('layout_json')->nullable();
            $table->json('regions_json')->nullable();
            $table->json('components_json')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('conditions_json')->nullable();
            $table->json('breakpoints_json')->nullable();
            $table->json('theme_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'module_key', 'page_key']);
            $table->index(['organization_id', 'workspace_id', 'route_path']);
        });

        Schema::create('ui_layouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('layout_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('layout_type', 64)->default('single_column');
            $table->string('status', 32)->default('published');
            $table->json('regions_json')->nullable();
            $table->json('breakpoints_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'layout_key']);
        });

        Schema::create('ui_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('component_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('component_type', 64)->default('custom');
            $table->string('status', 32)->default('published');
            $table->string('binding_type', 64)->nullable();
            $table->json('binding_config')->nullable();
            $table->json('actions_json')->nullable();
            $table->json('conditions_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'component_key']);
        });

        Schema::create('ui_personalizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('page_public_id')->nullable();
            $table->json('personalization_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'membership_id', 'page_public_id']);
        });

        Schema::create('ui_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('ui_page_id')->nullable();
            $table->uuid('ui_component_id')->nullable();
            $table->string('action', 128);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('ui_page_id')->references('id')->on('ui_pages')->nullOnDelete();
            $table->foreign('ui_component_id')->references('id')->on('ui_components')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_activity_logs');
        Schema::dropIfExists('ui_personalizations');
        Schema::dropIfExists('ui_components');
        Schema::dropIfExists('ui_layouts');
        Schema::dropIfExists('ui_pages');
    }
};
