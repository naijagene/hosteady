<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('package_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('author', 255)->nullable();
            $table->string('license', 128)->nullable();
            $table->string('visibility', 32)->default('organization');
            $table->string('type', 32)->default('solution');
            $table->string('status', 32)->default('draft');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'package_key']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('workflow_package_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_package_id');
            $table->string('version', 32);
            $table->json('manifest_json');
            $table->json('package_json');
            $table->string('checksum', 128);
            $table->string('status', 32)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('deprecated_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('workflow_package_id')->references('id')->on('workflow_packages')->restrictOnDelete();
            $table->unique(['workflow_package_id', 'version']);
            $table->index(['workflow_package_id', 'status']);
        });

        Schema::create('workflow_package_installs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('workflow_package_id');
            $table->uuid('workflow_package_version_id');
            $table->uuid('installed_workflow_definition_id')->nullable();
            $table->string('installed_version', 32);
            $table->string('status', 32)->default('installed');
            $table->timestampTz('installed_at');
            $table->timestampTz('upgraded_at')->nullable();
            $table->timestampTz('rolled_back_at')->nullable();
            $table->timestampTz('uninstalled_at')->nullable();
            $table->uuid('installed_by_user_id')->nullable();
            $table->uuid('installed_by_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('workflow_package_id')->references('id')->on('workflow_packages')->restrictOnDelete();
            $table->foreign('workflow_package_version_id')->references('id')->on('workflow_package_versions')->restrictOnDelete();
            $table->foreign('installed_workflow_definition_id')->references('id')->on('workflow_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('workflow_package_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_package_id');
            $table->string('dependency_key', 128);
            $table->string('dependency_type', 64);
            $table->string('version_constraint', 64)->nullable();
            $table->boolean('required')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('workflow_package_id')->references('id')->on('workflow_packages')->cascadeOnDelete();
            $table->index(['workflow_package_id', 'dependency_type']);
        });

        Schema::create('workflow_package_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('workflow_package_id')->nullable();
            $table->uuid('workflow_package_install_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('workflow_package_id')->references('id')->on('workflow_packages')->nullOnDelete();
            $table->foreign('workflow_package_install_id')->references('id')->on('workflow_package_installs')->nullOnDelete();
            $table->index(['workflow_package_install_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_package_history');
        Schema::dropIfExists('workflow_package_dependencies');
        Schema::dropIfExists('workflow_package_installs');
        Schema::dropIfExists('workflow_package_versions');
        Schema::dropIfExists('workflow_packages');
    }
};
