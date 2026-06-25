<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('capabilities')->nullable()->after('category');
            $table->json('dependencies')->nullable()->after('capabilities');
        });

        Schema::create('application_setting_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->foreignUuid('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->string('setting_key', 128);
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->string('setting_type', 32);
            $table->json('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->string('scope', 32)->default('workspace');
            $table->string('category', 64)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestampsTz();
            $table->timestampTz('deleted_at')->nullable();

            $table->index(['application_id', 'scope', 'status']);
        });

        $this->createPartialUniqueIndex(
            'application_setting_definitions_app_key_scope_unique_active',
            'application_setting_definitions',
            'application_id, setting_key, scope',
            'deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        $this->dropPartialIndex('application_setting_definitions_app_key_scope_unique_active');

        Schema::dropIfExists('application_setting_definitions');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['capabilities', 'dependencies']);
        });
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
