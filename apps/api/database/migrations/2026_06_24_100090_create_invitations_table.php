<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * public_id is the primary external identifier for APIs.
     * invitation_code is a human-friendly operational identifier (e.g. INV-000001).
     *
     * status allowed values: pending, accepted, expired, revoked
     *
     * accepted_membership_id is populated when an invitation is accepted and links
     * to the created organization_memberships row for traceability and reporting.
     *
     * Note: Organization Join Requests are deferred to a future RFC.
     * Workspace-scoped roles and permissions are deferred to a future RFC.
     * The owner role must not be assignable via invitation_roles.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('invitation_code', 32)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('email', 320);
            $table->foreignId('invited_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('token_hash', 255);
            $table->string('status', 32)->default('pending');
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('accepted_membership_id')
                ->nullable()
                ->constrained('organization_memberships')
                ->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'email', 'status']);
            $table->index('token_hash');
            $table->index('expires_at');
            $table->index('organization_id');
            $table->index('status');
            $table->index('accepted_membership_id');
        });

        $this->createPartialUniqueIndex(
            'invitations_org_email_pending_unique',
            'invitations',
            'organization_id, email',
            "status = 'pending' AND deleted_at IS NULL",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('invitations_org_email_pending_unique');

        Schema::dropIfExists('invitations');
    }

    private function createPartialUniqueIndex(
        string $indexName,
        string $table,
        string $columns,
        string $where,
    ): void {
        if (! $this->supportsPartialIndexes()) {
            return;
        }

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX %s ON %s (%s) WHERE %s',
            $indexName,
            $table,
            $columns,
            $where,
        ));
    }

    private function dropPartialIndex(string $indexName): void
    {
        if (! $this->supportsPartialIndexes()) {
            return;
        }

        DB::statement(sprintf('DROP INDEX IF EXISTS %s', $indexName));
    }

    private function supportsPartialIndexes(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['pgsql', 'sqlite'], true);
    }
};
