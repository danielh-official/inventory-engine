<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;

test('demo seeder is deterministic', function () {
    $snapshot = fn () => User::orderBy('id')
        ->get(['name', 'email', 'remember_token', 'created_at'])
        ->toArray();

    $this->seed(DemoSeeder::class);
    $first = $snapshot();

    User::query()->delete();
    $this->seed(DemoSeeder::class);

    expect($first)->toHaveCount(10)
        ->and($snapshot())->toBe($first)
        ->and($first[0]['created_at'])->toStartWith('2026-01-01');
});
