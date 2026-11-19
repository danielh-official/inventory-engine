<?php

namespace Database\Seeders;

use App\Actions\Stock\RecordAdjustment;
use App\Enums\AdjustmentReason;
use App\Models\Bin;
use App\Models\Category;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Deterministic demo data: the same seed gives the same rows on every machine.
 * Factories must take randomness from fake() and Str::random() only, never random_*() directly.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const SEED = 20260101;

    public const EPOCH = '2026-01-01 00:00:00';

    private const PRODUCTS = 200;

    public function run(): void
    {
        // Factories use the fake() singleton. Reset unique() so a second run in the same
        // process doesn't skip values the first run took.
        fake()->seed(self::SEED);
        fake()->unique(true);
        Str::createRandomStringsUsing(fn (int $length) => fake()->lexify(str_repeat('?', $length)));
        Carbon::setTestNow(self::EPOCH);

        try {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            User::factory(9)->create();

            $this->seedCatalogue();
            $this->seedWarehouses();
            $this->seedOpeningStock();
        } finally {
            Carbon::setTestNow();
            Str::createRandomStringsNormally();
            fake()->seed();
        }
    }

    private function seedCatalogue(): void
    {
        $each = UnitOfMeasure::create(['code' => 'EA', 'name' => 'Each']);
        $case = UnitOfMeasure::create(['code' => 'CS', 'name' => 'Case']);
        $kilo = UnitOfMeasure::create(['code' => 'KG', 'name' => 'Kilogram']);

        $categories = collect(['Fasteners', 'Electrical', 'Plumbing', 'Paint', 'Tools', 'Safety', 'Garden', 'Cleaning'])
            ->map(fn (string $name) => Category::create(['name' => $name]));

        foreach (range(1, self::PRODUCTS) as $i) {
            $byWeight = $i % 10 === 0;

            $product = Product::factory()->create([
                'sku' => sprintf('SKU-%05d', $i),
                'category_id' => fake()->randomElement($categories)->id,
                'base_unit_id' => $byWeight ? $kilo->id : $each->id,
            ]);

            if (! $byWeight && fake()->boolean(40)) {
                $product->unitConversions()->create([
                    'unit_id' => $case->id,
                    'base_units_per_unit' => fake()->randomElement([6, 12, 24, 48]),
                ]);
            }
        }
    }

    private function seedWarehouses(): void
    {
        $warehouses = [
            ['code' => 'NYC', 'name' => 'New York DC', 'timezone' => 'America/New_York'],
            ['code' => 'BER', 'name' => 'Berlin DC', 'timezone' => 'Europe/Berlin'],
            ['code' => 'TYO', 'name' => 'Tokyo DC', 'timezone' => 'Asia/Tokyo'],
        ];

        foreach ($warehouses as $attributes) {
            $warehouse = Warehouse::create($attributes);

            // Aisles A-D, racks 01-05, levels 1-2: 40 bins per warehouse.
            foreach (['A', 'B', 'C', 'D'] as $aisle) {
                foreach (range(1, 5) as $rack) {
                    foreach (range(1, 2) as $level) {
                        $warehouse->bins()->create(['code' => sprintf('%s-%02d-%d', $aisle, $rack, $level)]);
                    }
                }
            }
        }
    }

    private function seedOpeningStock(): void
    {
        $record = app(RecordAdjustment::class);
        $bins = Bin::query()->orderBy('id')->get();
        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        Product::query()->orderBy('id')->each(function (Product $product) use ($record, $bins, $admin) {
            foreach (fake()->randomElements($bins, fake()->numberBetween(1, 4)) as $bin) {
                $record->handle(
                    $product,
                    $bin,
                    fake()->numberBetween(1, 500),
                    AdjustmentReason::CountCorrection,
                    'Opening balance',
                    $admin,
                );
            }
        });
    }
}
