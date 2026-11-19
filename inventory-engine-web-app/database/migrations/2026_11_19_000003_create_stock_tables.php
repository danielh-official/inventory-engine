<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('bin_id')->constrained();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'bin_id']);
            $table->index('bin_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('bin_id')->constrained();
            $table->integer('quantity_delta');
            $table->string('reason', 32);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestampTz('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
    }
};
