<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('module_key', 128)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('type', 32)->default('business');
            $table->string('status', 32)->default('draft');
            $table->string('version', 32)->default('0.1.0');
            $table->json('manifest_json');
            $table->json('capabilities')->nullable();
            $table->json('permissions')->nullable();
            $table->json('routes')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('installed_at')->nullable();
            $table->timestampTz('enabled_at')->nullable();
            $table->timestampTz('disabled_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['status', 'type']);
        });

        Schema::create('business_module_installations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('business_module_id');
            $table->string('installed_version', 32);
            $table->string('status', 32)->default('installed');
            $table->json('settings')->nullable();
            $table->timestampTz('installed_at');
            $table->timestampTz('enabled_at')->nullable();
            $table->timestampTz('disabled_at')->nullable();
            $table->uuid('installed_by_user_id')->nullable();
            $table->uuid('installed_by_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('business_module_id')->references('id')->on('business_modules')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('business_module_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('business_module_id')->nullable();
            $table->uuid('business_module_installation_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('business_module_id')->references('id')->on('business_modules')->nullOnDelete();
            $table->foreign('business_module_installation_id')->references('id')->on('business_module_installations')->nullOnDelete();
            $table->index(['business_module_installation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_module_history');
        Schema::dropIfExists('business_module_installations');
        Schema::dropIfExists('business_modules');
    }
};
