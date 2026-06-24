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
     * plan_tier allowed values: free, starter, business, enterprise
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('organization_code', 32)->nullable();
            $table->string('name', 255);
            $table->string('slug', 63);
            $table->string('status', 32)->default('provisioning');
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 16)->default('en');
            $table->string('plan_tier', 64)->default('free');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('owner_user_id');
            $table->index(['status', 'deleted_at']);
        });

        $this->createPartialUniqueIndex(
            'organizations_code_unique_active',
            'organizations',
            'organization_code',
            'deleted_at IS NULL AND organization_code IS NOT NULL',
        );

        $this->createPartialUniqueIndex(
            'organizations_slug_unique_active',
            'organizations',
            'slug',
            'deleted_at IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('organizations_slug_unique_active');
        $this->dropPartialIndex('organizations_code_unique_active');

        Schema::dropIfExists('organizations');
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
