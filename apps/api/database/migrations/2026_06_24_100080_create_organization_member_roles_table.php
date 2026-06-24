<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membership-based authorization junction. Roles assigned here must belong to the
     * same organization as the membership (enforced in application layer).
     *
     * Note: Workspace-scoped roles and permissions are deferred to a future RFC.
     */
    public function up(): void
    {
        Schema::create('organization_member_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_membership_id')
                ->constrained('organization_memberships')
                ->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('updated_at');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_membership_id', 'role_id'], 'org_member_roles_membership_role_unique');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_member_roles');
    }
};
