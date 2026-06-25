<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_application_setting_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->foreignUuid('workspace_application_setting_id')
                ->nullable()
                ->constrained('workspace_application_settings')
                ->nullOnDelete();
            $table->foreignUuid('workspace_application_id')
                ->constrained('workspace_applications')
                ->restrictOnDelete();
            $table->string('setting_key', 128);
            $table->unsignedInteger('version');
            $table->string('change_type', 32);
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('changed_by_membership_id')
                ->nullable()
                ->constrained('organization_memberships')
                ->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestampTz('created_at');

            $table->index(['workspace_application_id', 'setting_key', 'created_at']);
            $table->index(['workspace_application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_application_setting_history');
    }
};
