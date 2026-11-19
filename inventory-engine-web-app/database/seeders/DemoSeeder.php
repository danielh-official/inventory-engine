<?php

namespace Database\Seeders;

use App\Models\User;
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
        } finally {
            Carbon::setTestNow();
            Str::createRandomStringsNormally();
            fake()->seed();
        }
    }
}
