<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['organization_id', 'occurred_at', 'id'], 'audit_logs_org_occurred_id_idx');
            $table->index(['organization_id', 'severity', 'occurred_at', 'id'], 'audit_logs_org_severity_occurred_idx');
            $table->index(['organization_id', 'actor_membership_id', 'occurred_at', 'id'], 'audit_logs_org_actor_membership_occurred_idx');
            $table->index(['organization_id', 'workspace_id', 'occurred_at', 'id'], 'audit_logs_org_workspace_occurred_idx');
            $table->index(['organization_id', 'entity_type', 'entity_public_id', 'occurred_at', 'id'], 'audit_logs_org_entity_occurred_idx');
            $table->index(['organization_id', 'action', 'occurred_at', 'id'], 'audit_logs_org_action_occurred_idx');
            $table->index(['organization_id', 'request_id', 'occurred_at', 'id'], 'audit_logs_org_request_id_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_org_occurred_id_idx');
            $table->dropIndex('audit_logs_org_severity_occurred_idx');
            $table->dropIndex('audit_logs_org_actor_membership_occurred_idx');
            $table->dropIndex('audit_logs_org_workspace_occurred_idx');
            $table->dropIndex('audit_logs_org_entity_occurred_idx');
            $table->dropIndex('audit_logs_org_action_occurred_idx');
            $table->dropIndex('audit_logs_org_request_id_occurred_idx');
        });
    }
};
