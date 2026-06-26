<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('module_key', 64);
            $table->string('type', 128);
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->json('channels')->nullable();
            $table->timestampsTz();

            $table->unique(['module_key', 'type']);
        });

        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('recipient_membership_id');
            $table->string('module_key', 64)->nullable();
            $table->string('type', 128);
            $table->string('title', 255);
            $table->text('body');
            $table->json('data')->nullable();
            $table->json('subject_reference')->nullable();
            $table->string('channel', 32);
            $table->string('status', 32);
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('deleted_by_user_id')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('recipient_membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'recipient_membership_id', 'read_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('membership_id');
            $table->string('channel', 32);
            $table->string('type', 128);
            $table->boolean('enabled')->default(true);
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->unique(['membership_id', 'channel', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('notification_templates');
    }
};
