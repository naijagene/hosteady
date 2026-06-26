<?php

namespace Database\Seeders;

use App\Models\ReferenceCatalog;
use App\Models\ReferenceItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCatalog('currencies', 'Currencies', [
            ['code' => 'USD', 'label' => 'US Dollar', 'metadata' => ['symbol' => '$']],
            ['code' => 'EUR', 'label' => 'Euro', 'metadata' => ['symbol' => '€']],
            ['code' => 'GBP', 'label' => 'British Pound', 'metadata' => ['symbol' => '£']],
            ['code' => 'NGN', 'label' => 'Nigerian Naira', 'metadata' => ['symbol' => '₦']],
        ]);

        $this->seedCatalog('countries', 'Countries', [
            ['code' => 'US', 'label' => 'United States', 'metadata' => ['iso3' => 'USA']],
            ['code' => 'GB', 'label' => 'United Kingdom', 'metadata' => ['iso3' => 'GBR']],
            ['code' => 'NG', 'label' => 'Nigeria', 'metadata' => ['iso3' => 'NGA']],
        ]);

        $this->seedCatalog('measurement_units', 'Measurement Units', [
            ['code' => 'kg', 'label' => 'Kilogram', 'metadata' => ['system' => 'metric']],
            ['code' => 'g', 'label' => 'Gram', 'metadata' => ['system' => 'metric']],
            ['code' => 'lb', 'label' => 'Pound', 'metadata' => ['system' => 'imperial']],
            ['code' => 'ml', 'label' => 'Millilitre', 'metadata' => ['system' => 'metric']],
            ['code' => 'l', 'label' => 'Litre', 'metadata' => ['system' => 'metric']],
        ]);
    }

    /**
     * @param  list<array{code: string, label: string, metadata: array<string, mixed>}>  $items
     */
    private function seedCatalog(string $key, string $name, array $items): void
    {
        $catalog = ReferenceCatalog::query()->firstOrCreate(
            ['key' => $key],
            [
                'id' => (string) Str::uuid7(),
                'name' => $name,
                'version' => 1,
                'description' => sprintf('Platform reference catalog: %s', $name),
            ],
        );

        foreach ($items as $index => $item) {
            ReferenceItem::query()->updateOrCreate(
                [
                    'reference_catalog_id' => $catalog->id,
                    'code' => $item['code'],
                ],
                [
                    'id' => (string) Str::uuid7(),
                    'label' => $item['label'],
                    'metadata' => $item['metadata'],
                    'sort_order' => ($index + 1) * 10,
                    'active' => true,
                ],
            );
        }
    }
}
