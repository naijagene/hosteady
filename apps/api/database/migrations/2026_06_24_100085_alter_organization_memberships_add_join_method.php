<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * join_method allowed values: invitation, self_register, system
     *
     * - invitation: membership created via invitation accept
     * - self_register: reserved for future self-serve onboarding / join-request RFC
     * - system: organization provisioning (owner/creator membership)
     *
     * Note: Organization Join Requests are deferred to a future RFC.
     */
    public function up(): void
    {
        Schema::table('organization_memberships', function (Blueprint $table) {
            $table->string('join_method', 32)->default('invitation')->after('invited_by_user_id');
            $table->index('join_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_memberships', function (Blueprint $table) {
            $table->dropIndex(['join_method']);
            $table->dropColumn('join_method');
        });
    }
};
