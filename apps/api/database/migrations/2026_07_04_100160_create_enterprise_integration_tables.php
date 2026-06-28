<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('event_name', 255);
            $table->string('event_version', 32)->nullable();
            $table->string('direction', 32)->default('internal');
            $table->string('source_type', 64)->default('platform');
            $table->string('source_module_key', 64)->nullable();
            $table->string('source_entity_key', 128)->nullable();
            $table->uuid('source_public_id')->nullable();
            $table->string('correlation_id', 128)->nullable();
            $table->string('idempotency_key', 255)->nullable();
            $table->string('status', 32)->default('published');
            $table->json('payload_json')->nullable();
            $table->json('headers_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'event_name']);
            $table->index(['organization_id', 'idempotency_key', 'event_name']);
            $table->index(['correlation_id']);
        });

        Schema::create('integration_event_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('subscription_key', 128);
            $table->string('event_pattern', 255);
            $table->string('endpoint_key', 128)->nullable();
            $table->string('status', 32)->default('enabled');
            $table->json('filters_json')->nullable();
            $table->json('transform_json')->nullable();
            $table->json('retry_policy_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'status']);
            $table->index(['event_pattern']);
        });

        Schema::create('integration_connectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('connector_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('connector_type', 64);
            $table->string('auth_type', 64)->default('none');
            $table->string('status', 32)->default('enabled');
            $table->json('config_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('integration_connector_id')->nullable();
            $table->string('endpoint_key', 128);
            $table->string('name', 255);
            $table->string('endpoint_type', 64);
            $table->string('direction', 32)->default('outbound');
            $table->string('status', 32)->default('enabled');
            $table->string('url_template', 512)->nullable();
            $table->string('method', 16)->nullable();
            $table->json('headers_json')->nullable();
            $table->json('body_template_json')->nullable();
            $table->json('auth_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('integration_connector_id')->references('id')->on('integration_connectors')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
        });

        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('connector_key', 128);
            $table->string('credential_key', 128);
            $table->string('auth_type', 64);
            $table->text('encrypted_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('rotated_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'connector_key', 'credential_key']);
        });

        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('mapping_key', 128);
            $table->json('source_schema')->nullable();
            $table->json('target_schema')->nullable();
            $table->json('mapping_json')->nullable();
            $table->string('transform_type', 64)->default('pass_through');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id']);
        });

        Schema::create('integration_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('integration_event_id')->nullable();
            $table->uuid('integration_endpoint_id')->nullable();
            $table->string('subscription_key', 128)->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('dispatched_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('next_retry_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('integration_event_id')->references('id')->on('integration_events')->nullOnDelete();
            $table->foreign('integration_endpoint_id')->references('id')->on('integration_endpoints')->nullOnDelete();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('integration_dead_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('integration_event_id')->nullable();
            $table->uuid('integration_dispatch_id')->nullable();
            $table->string('status', 32)->default('open');
            $table->string('reason', 255);
            $table->json('payload_json')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('resolved_at')->nullable();
            $table->uuid('resolved_by_user_id')->nullable();
            $table->uuid('resolved_by_membership_id')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('integration_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('integration_event_id')->nullable();
            $table->uuid('integration_connector_id')->nullable();
            $table->uuid('integration_endpoint_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_activity_logs');
        Schema::dropIfExists('integration_dead_letters');
        Schema::dropIfExists('integration_dispatches');
        Schema::dropIfExists('integration_mappings');
        Schema::dropIfExists('integration_credentials');
        Schema::dropIfExists('integration_endpoints');
        Schema::dropIfExists('integration_connectors');
        Schema::dropIfExists('integration_event_subscriptions');
        Schema::dropIfExists('integration_events');
    }
};
