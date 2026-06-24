<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('status', 32)->default('installing');
            $table->string('installed_version', 32);
            $table->json('config')->nullable();
            $table->timestampTz('installed_at')->nullable();
            $table->foreignId('installed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('installed_by_membership_id')
                ->constrained('organization_memberships')
                ->restrictOnDelete();
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'status']);
            $table->index('application_id');
            $table->index('installed_by_membership_id');
        });

        $this->createPartialUniqueIndex(
            'organization_applications_org_app_unique_active',
            'organization_applications',
            'organization_id, application_id',
            'deleted_at IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('organization_applications_org_app_unique_active');

        Schema::dropIfExists('organization_applications');
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
