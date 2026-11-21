<?php

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users of both roles. Admin rows have no NIK or phone number.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->nullable()->unique();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', array_column(UserRole::cases(), 'value'));
            $table->enum('status', array_column(RegistrationStatus::cases(), 'value'))
                ->default(RegistrationStatus::Pending->value);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
