<?php

use App\Enums\GarageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garages created by the admin in batches. Prices are whole rupiah.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->char('code', 4)->unique();
            $table->enum('type', array_column(GarageType::cases(), 'value'));
            $table->unsignedBigInteger('daily_price');
            $table->unsignedBigInteger('charging_price_per_kwh')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
