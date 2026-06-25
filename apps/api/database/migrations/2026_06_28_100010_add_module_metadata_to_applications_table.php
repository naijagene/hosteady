<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const MODULE_UUIDS = [
        'core' => '01900000-0000-7000-8000-000000000001',
        'workspace' => '01900000-0000-7000-8000-000000000002',
        'demo' => '01900000-0000-7000-8000-000000000003',
    ];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('module_uuid')->nullable()->unique()->after('public_id');
            $table->unsignedInteger('manifest_version')->default(1)->after('module_uuid');
        });

        foreach (self::MODULE_UUIDS as $key => $moduleUuid) {
            DB::table('applications')
                ->where('key', $key)
                ->update([
                    'module_uuid' => $moduleUuid,
                    'manifest_version' => 1,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['module_uuid']);
            $table->dropColumn(['module_uuid', 'manifest_version']);
        });
    }
};
