<?php

namespace Database\Factories;

use App\Models\Bin;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bin>
 */
class BinFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            // Aisle-rack-level, e.g. A07-03-2.
            'code' => fake()->unique()->bothify('?##-##-#'),
        ];
    }
}
