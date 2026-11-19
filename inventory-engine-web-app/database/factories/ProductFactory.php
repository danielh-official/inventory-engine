<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-#####-??'),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'base_unit_id' => UnitOfMeasure::factory(),
            'attributes' => [
                'colour' => fake()->safeColorName(),
                'weight_g' => fake()->numberBetween(10, 25_000),
                'hazmat' => fake()->boolean(5),
            ],
        ];
    }

    public function hazmat(): static
    {
        return $this->state(fn (array $attributes) => [
            'attributes' => ['hazmat' => true] + ($attributes['attributes'] ?? []),
        ]);
    }
}
