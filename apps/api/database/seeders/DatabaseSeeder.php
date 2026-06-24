<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'public_id' => (string) Str::uuid7(),
            'status' => 'active',
        ]);

        $this->call(PlatformBootstrapSeeder::class);
    }
}
