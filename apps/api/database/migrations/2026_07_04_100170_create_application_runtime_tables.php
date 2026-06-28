<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_runtime_apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('application_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('application_type', 64)->default('business');
            $table->string('status', 32)->default('registered');
            $table->string('visibility', 32)->default('workspace');
            $table->string('module_key', 64)->nullable();
            $table->uuid('catalog_application_id')->nullable();
            $table->json('manifest_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('catalog_application_id')->references('id')->on('applications')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'application_key']);
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('application_workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_runtime_app_id');
            $table->string('workspace_key', 128);
            $table->string('name', 255);
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('application_runtime_app_id')->references('id')->on('application_runtime_apps')->cascadeOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('application_navigation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_runtime_app_id')->nullable();
            $table->string('menu_key', 128);
            $table->string('navigation_key', 128);
            $table->string('label', 255);
            $table->string('item_type', 64)->default('item');
            $table->string('parent_key', 128)->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('route_json')->nullable();
            $table->json('badge_json')->nullable();
            $table->string('required_permission', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('application_runtime_app_id')->references('id')->on('application_runtime_apps')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'menu_key']);
        });

        Schema::create('application_runtime_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('cache_key', 255);
            $table->json('payload_json')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'cache_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_runtime_cache');
        Schema::dropIfExists('application_navigation');
        Schema::dropIfExists('application_workspaces');
        Schema::dropIfExists('application_runtime_apps');
    }
};
