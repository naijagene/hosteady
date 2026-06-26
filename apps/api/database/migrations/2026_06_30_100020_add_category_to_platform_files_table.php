<?php

use App\Services\Enterprise\FileMedia\FileCategoryClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_files', function (Blueprint $table) {
            $table->string('category', 32)->default('other')->after('mime_type');
        });

        $classifier = new FileCategoryClassifier;

        DB::table('platform_files')->orderBy('id')->chunkById(100, function ($files) use ($classifier) {
            foreach ($files as $file) {
                DB::table('platform_files')
                    ->where('id', $file->id)
                    ->update([
                        'category' => $classifier->classify($file->mime_type, $file->extension)->value,
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_files', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
