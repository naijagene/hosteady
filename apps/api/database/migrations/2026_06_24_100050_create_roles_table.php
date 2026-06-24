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
     * status allowed values: active, inactive, deprecated
     *
     * Default system role keys (seeded per organization):
     * owner, administrator, manager, member, viewer
     *
     * Note: Workspace-scoped roles and permissions are deferred to a future RFC.
     * organization_id NULL indicates a platform role; NOT NULL indicates a tenant role.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->string('key', 64);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('status', 32)->default('active');
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index('status');
        });

        $this->createPartialUniqueIndex(
            'roles_org_key_unique_active',
            'roles',
            'organization_id, key',
            'organization_id IS NOT NULL AND deleted_at IS NULL',
        );

        $this->createPartialUniqueIndex(
            'roles_platform_key_unique_active',
            'roles',
            'key',
            'organization_id IS NULL AND deleted_at IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('roles_platform_key_unique_active');
        $this->dropPartialIndex('roles_org_key_unique_active');

        Schema::dropIfExists('roles');
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
