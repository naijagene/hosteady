<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasExistingUsers = DB::table('users')->exists();

        Schema::table('users', function (Blueprint $table) use ($hasExistingUsers) {
            if ($hasExistingUsers) {
                $table->uuid('public_id')->nullable()->after('id');
                $table->string('display_name', 255)->nullable()->after('name');
            } else {
                $table->uuid('public_id')->after('id');
                $table->string('display_name', 255)->after('name');
            }

            $table->string('status', 32)->default('active')->after('remember_token');
            $table->foreignId('created_by_user_id')->nullable()->after('updated_at');
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id');
            $table->timestampTz('deleted_at')->nullable()->after('updated_by_user_id');
            $table->foreignId('deleted_by_user_id')->nullable()->after('deleted_at');
        });

        if ($hasExistingUsers) {
            DB::table('users')
                ->orderBy('id')
                ->lazy()
                ->each(function (object $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'public_id' => (string) Str::uuid7(),
                            'display_name' => $user->name,
                        ]);
                });

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE users ALTER COLUMN public_id SET NOT NULL');
                DB::statement('ALTER TABLE users ALTER COLUMN display_name SET NOT NULL');
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('public_id');
            $table->index('status');
            $table->index('deleted_at');

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('deleted_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        $this->createPartialUniqueIndex(
            'users_email_unique_active',
            'users',
            'email',
            'deleted_at IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPartialIndex('users_email_unique_active');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['updated_by_user_id']);
            $table->dropForeign(['deleted_by_user_id']);
            $table->dropUnique(['public_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn([
                'public_id',
                'display_name',
                'status',
                'created_by_user_id',
                'updated_by_user_id',
                'deleted_at',
                'deleted_by_user_id',
            ]);
            $table->unique('email');
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
