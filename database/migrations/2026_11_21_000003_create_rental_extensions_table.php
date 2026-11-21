<?php

use App\Enums\RentalType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log of rental extensions made by the admin, with the extra fee and its proof of payment.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->enum('rental_type', array_column(RentalType::cases(), 'value'));
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->unsignedBigInteger('fee');
            $table->string('payment_proof_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};
