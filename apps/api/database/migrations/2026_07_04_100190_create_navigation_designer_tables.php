<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('navigation_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 64)->default('primary');
            $table->string('status', 32)->default('draft');
            $table->string('visibility', 32)->default('authenticated');
            $table->string('scope', 32)->default('workspace');
            $table->uuid('current_version_id')->nullable();
            $table->json('structure_json')->nullable();
            $table->json('conditions_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'navigation_key']);
        });

        Schema::create('navigation_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('navigation_definition_id');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status', 32)->default('draft');
            $table->json('structure_json')->nullable();
            $table->json('conditions_json')->nullable();
            $table->text('change_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->uuid('published_by_user_id')->nullable();
            $table->uuid('published_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('navigation_definition_id')->references('id')->on('navigation_definitions')->cascadeOnDelete();
            $table->index(['navigation_definition_id', 'version_number']);
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('navigation_definition_id')->nullable();
            $table->uuid('parent_item_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('item_key', 128);
            $table->string('label', 255);
            $table->string('item_type', 64)->default('link');
            $table->string('route', 512)->nullable();
            $table->string('icon', 128)->nullable();
            $table->json('badge_json')->nullable();
            $table->string('visibility', 32)->default('authenticated');
            $table->json('conditions_json')->nullable();
            $table->json('permissions_json')->nullable();
            $table->json('roles_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('navigation_definition_id')->references('id')->on('navigation_definitions')->nullOnDelete();
            $table->foreign('parent_item_id')->references('id')->on('navigation_items')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'navigation_definition_id', 'sort_order']);
        });

        Schema::create('navigation_personalizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('navigation_definition_id')->nullable();
            $table->json('personalization_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'membership_id', 'navigation_definition_id']);
        });

        Schema::create('navigation_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('navigation_definition_id')->nullable();
            $table->uuid('navigation_item_id')->nullable();
            $table->string('action', 128);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('navigation_definition_id')->references('id')->on('navigation_definitions')->nullOnDelete();
            $table->foreign('navigation_item_id')->references('id')->on('navigation_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_activity_logs');
        Schema::dropIfExists('navigation_personalizations');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_versions');
        Schema::dropIfExists('navigation_definitions');
    }
};
