<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_templates', 'template_type')) {
                $table->string('template_type', 32)->default('module')->after('type');
            }
            if (! Schema::hasColumn('notification_templates', 'variables_json')) {
                $table->json('variables_json')->nullable()->after('channels');
            }
            if (! Schema::hasColumn('notification_templates', 'scope')) {
                $table->string('scope', 32)->default('organization')->after('variables_json');
            }
            if (! Schema::hasColumn('notification_templates', 'organization_id')) {
                $table->uuid('organization_id')->nullable()->after('scope');
                $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            }
            if (! Schema::hasColumn('notification_templates', 'deleted_at')) {
                $table->softDeletesTz();
            }
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_preferences', 'preferred_channels_json')) {
                $table->json('preferred_channels_json')->nullable()->after('enabled');
            }
            if (! Schema::hasColumn('notification_preferences', 'digest_frequency')) {
                $table->string('digest_frequency', 32)->nullable()->after('preferred_channels_json');
            }
            if (! Schema::hasColumn('notification_preferences', 'quiet_hours_json')) {
                $table->json('quiet_hours_json')->nullable()->after('digest_frequency');
            }
        });

        Schema::create('enterprise_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('scope', 32)->default('user');
            $table->string('priority', 32)->default('normal');
            $table->string('status', 32)->default('pending');
            $table->string('title', 255);
            $table->text('body');
            $table->string('template_key', 128)->nullable();
            $table->json('merge_data')->nullable();
            $table->json('channels')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('sender_membership_id')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->uuid('deleted_by_user_id')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('sender_membership_id')->references('id')->on('organization_memberships')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_notification_id');
            $table->uuid('notification_public_id');
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('recipient_membership_id');
            $table->string('channel', 32);
            $table->string('status', 32)->default('pending');
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('enterprise_notification_id')->references('id')->on('enterprise_notifications')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('recipient_membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'recipient_membership_id', 'status']);
            $table->index(['notification_public_id', 'channel']);
        });

        Schema::create('notification_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('enterprise_notification_id')->nullable();
            $table->uuid('notification_public_id')->nullable();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->string('action', 64);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('enterprise_notification_id')->references('id')->on('enterprise_notifications')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->index(['organization_id', 'notification_public_id']);
        });

        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('membership_id');
            $table->string('event_type', 128);
            $table->json('channels')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->unique(['membership_id', 'event_type']);
        });

        Schema::create('notification_digests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('membership_id');
            $table->string('frequency', 32)->default('daily');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('notification_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestampTz('generated_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'membership_id', 'status']);
        });

        Schema::create('notification_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('membership_id');
            $table->string('title', 255);
            $table->string('cron_expression', 128);
            $table->string('template_key', 128)->nullable();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreign('membership_id')->references('id')->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_schedules');
        Schema::dropIfExists('notification_digests');
        Schema::dropIfExists('notification_subscriptions');
        Schema::dropIfExists('notification_activity_logs');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('enterprise_notifications');

        Schema::table('notification_preferences', function (Blueprint $table) {
            if (Schema::hasColumn('notification_preferences', 'quiet_hours_json')) {
                $table->dropColumn('quiet_hours_json');
            }
            if (Schema::hasColumn('notification_preferences', 'digest_frequency')) {
                $table->dropColumn('digest_frequency');
            }
            if (Schema::hasColumn('notification_preferences', 'preferred_channels_json')) {
                $table->dropColumn('preferred_channels_json');
            }
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            if (Schema::hasColumn('notification_templates', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('notification_templates', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            }
            foreach (['scope', 'variables_json', 'template_type'] as $column) {
                if (Schema::hasColumn('notification_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
