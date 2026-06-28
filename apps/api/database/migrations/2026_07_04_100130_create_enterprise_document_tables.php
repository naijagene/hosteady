<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->string('visibility', 32)->default('organization');
            $table->string('category', 32)->default('general');
            $table->string('module_key', 128)->nullable();
            $table->uuid('current_version_id')->nullable();
            $table->string('retention_action', 32)->default('none');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
            $table->index(['organization_id', 'module_key', 'deleted_at']);
            $table->index(['organization_id', 'category', 'status']);
        });

        Schema::create('enterprise_document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->unsignedInteger('version_number');
            $table->uuid('platform_file_public_id');
            $table->uuid('platform_file_id')->nullable();
            $table->string('status', 32)->default('active');
            $table->string('label', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('platform_file_id')->references('id')->on('platform_files')->nullOnDelete();
            $table->unique(['enterprise_document_id', 'version_number'], 'enterprise_document_versions_unique');
            $table->index(['organization_id', 'document_public_id']);
            $table->index(['platform_file_public_id']);
        });

        Schema::table('enterprise_documents', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('enterprise_document_versions')->nullOnDelete();
        });

        Schema::create('enterprise_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('subject_type', 64);
            $table->uuid('subject_public_id');
            $table->string('subject_module_key', 128)->nullable();
            $table->string('subject_entity_key', 128)->nullable();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'document_public_id']);
            $table->index(['subject_type', 'subject_public_id']);
            $table->index(['organization_id', 'subject_type', 'subject_public_id']);
        });

        Schema::create('enterprise_document_previews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('version_public_id')->nullable();
            $table->uuid('enterprise_document_version_id')->nullable();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('preview_format', 32)->nullable();
            $table->uuid('platform_file_public_id')->nullable();
            $table->uuid('platform_file_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('enterprise_document_version_id')->references('id')->on('enterprise_document_versions')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('platform_file_id')->references('id')->on('platform_files')->nullOnDelete();
            $table->index(['organization_id', 'document_public_id', 'status']);
        });

        Schema::create('enterprise_document_thumbnails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('version_public_id')->nullable();
            $table->uuid('enterprise_document_version_id')->nullable();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->uuid('platform_file_public_id')->nullable();
            $table->uuid('platform_file_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('enterprise_document_version_id')->references('id')->on('enterprise_document_versions')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('platform_file_id')->references('id')->on('platform_files')->nullOnDelete();
            $table->index(['organization_id', 'document_public_id', 'status']);
        });

        Schema::create('enterprise_document_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'document_public_id', 'status']);
        });

        Schema::create('enterprise_document_ocr_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_document_id');
            $table->uuid('document_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->longText('ocr_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'document_public_id', 'status']);
        });

        Schema::create('enterprise_document_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('document_public_id');
            $table->uuid('enterprise_document_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('enterprise_document_id')->references('id')->on('enterprise_documents')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'document_public_id']);
            $table->index(['document_public_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_document_activity');
        Schema::dropIfExists('enterprise_document_ocr_results');
        Schema::dropIfExists('enterprise_document_scans');
        Schema::dropIfExists('enterprise_document_thumbnails');
        Schema::dropIfExists('enterprise_document_previews');
        Schema::dropIfExists('enterprise_attachments');

        Schema::table('enterprise_documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('enterprise_document_versions');
        Schema::dropIfExists('enterprise_documents');
    }
};
