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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('name', 255);
            $table->string('slug', 63);
            $table->boolean('is_default')->default(false);
            $table->string('status', 32)->default('active');
            $table->timestampsTz();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('organization_id');
            $table->index(['organization_id', 'is_default']);
            $table->index('status');
        });

        $this->createPartialUniqueIndex(
            'workspaces_org_slug_unique_active',
            'workspaces',
            'organization_id, slug',
            'deleted_at IS NULL',
        );

        $this->createPartialUniqueIndex(
            'workspaces_one_default_per_org',
            'workspaces',
            'organization_id',
            $this->defaultWorkspacePartialCondition(),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('workspaces_one_default_per_org');
        $this->dropPartialIndex('workspaces_org_slug_unique_active');

        Schema::dropIfExists('workspaces');
    }

    private function defaultWorkspacePartialCondition(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'is_default = true AND deleted_at IS NULL'
            : 'is_default = 1 AND deleted_at IS NULL';
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
