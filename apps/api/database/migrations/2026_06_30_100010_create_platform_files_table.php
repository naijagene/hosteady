<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->json('entity_reference')->nullable();
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('extension', 32);
            $table->string('mime_type', 128);
            $table->string('checksum', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->string('visibility', 32);
            $table->string('storage_disk', 64);
            $table->string('storage_path', 512);
            $table->uuid('uploaded_by_user_id');
            $table->uuid('uploaded_membership_id');
            $table->string('display_name', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('uploaded_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('uploaded_membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'workspace_id', 'deleted_at']);
            $table->index(['organization_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_files');
    }
};
