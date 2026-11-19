<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('bin_id')->constrained();
            $table->integer('quantity_delta');
            $table->string('reason', 32);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('stock_movement_id')->unique()->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
