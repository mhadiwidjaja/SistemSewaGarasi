<?php

use App\Enums\RentalType;
use App\Enums\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garage rentals, including the vehicle details entered by the renter.
 * The end date is moved forward when the rental is extended.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('renter_id')->constrained('users');
            $table->foreignId('garage_id')->constrained();
            $table->enum('vehicle_type', array_column(VehicleType::cases(), 'value'));
            $table->string('plate_number', 15)->nullable();
            $table->string('vehicle_brand_type', 100);
            $table->decimal('battery_capacity_kwh', 6, 2)->nullable();
            $table->decimal('battery_left_kwh', 6, 2)->nullable();
            $table->enum('rental_type', array_column(RentalType::cases(), 'value'));
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('rental_fee');
            $table->unsignedBigInteger('charging_fee')->default(0);
            $table->string('payment_proof_path');
            $table->timestamps();

            $table->index(['garage_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
