<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('WH-??#'),
            'name' => fake()->city().' DC',
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/Berlin', 'Asia/Tokyo']),
        ];
    }
}
