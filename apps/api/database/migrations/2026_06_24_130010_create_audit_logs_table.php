<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->timestampTz('occurred_at');
            $table->string('scope', 32);
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('actor_membership_id')->nullable()->constrained('organization_memberships')->nullOnDelete();
            $table->string('actor_type', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('request_id')->nullable();
            $table->string('category', 32);
            $table->string('action', 64);
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->string('severity', 32);
            $table->string('summary', 255);
            $table->string('entity_type', 64)->nullable();
            $table->uuid('entity_public_id')->nullable();
            $table->string('entity_label', 255)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('metadata')->nullable();
            $table->string('retention_class', 32);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at');

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['organization_id', 'category', 'occurred_at']);
            $table->index(['entity_type', 'entity_public_id', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index('expires_at');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
