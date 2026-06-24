<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pre-assigns organization roles offered when an invitation is accepted.
     * role.organization_id must match invitation.organization_id (application layer).
     * The owner role must not be assignable via this junction.
     *
     * Note: Organization Join Requests are deferred to a future RFC.
     * Workspace-scoped roles and permissions are deferred to a future RFC.
     */
    public function up(): void
    {
        Schema::create('invitation_roles', function (Blueprint $table) {
            $table->foreignUuid('invitation_id')->constrained('invitations')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->primary(['invitation_id', 'role_id']);
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_roles');
    }
};
