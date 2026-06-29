<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('theme_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('scope', 32)->default('workspace');
            $table->string('inheritance_mode', 32)->default('none');
            $table->uuid('parent_theme_id')->nullable();
            $table->uuid('current_version_id')->nullable();
            $table->json('tokens_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'theme_key']);
        });

        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id')->nullable();
            $table->string('name', 255);
            $table->string('logo_url', 512)->nullable();
            $table->json('colors_json')->nullable();
            $table->json('typography_json')->nullable();
            $table->json('assets_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'theme_definition_id']);
        });

        Schema::create('theme_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status', 32)->default('draft');
            $table->json('snapshot_json')->nullable();
            $table->text('change_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->uuid('published_by_user_id')->nullable();
            $table->uuid('published_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->cascadeOnDelete();
            $table->index(['theme_definition_id', 'version_number']);
        });

        Schema::create('theme_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id')->nullable();
            $table->uuid('brand_profile_id')->nullable();
            $table->string('action', 128);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->nullOnDelete();
            $table->foreign('brand_profile_id')->references('id')->on('brand_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_activity_logs');
        Schema::dropIfExists('theme_versions');
        Schema::dropIfExists('brand_profiles');
        Schema::dropIfExists('theme_definitions');
    }
};
