<?php

namespace App\Models;

use App\Enums\RentalStatus;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One garage rented by one renter for one vehicle over a period (start and end inclusive).
 *
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 */
class Rental extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'garage_id',
        'vehicle_type',
        'plate_number',
        'vehicle_brand_type',
        'battery_capacity_kwh',
        'battery_left_kwh',
        'rental_type',
        'start_date',
        'end_date',
        'rental_fee',
        'charging_fee',
        'payment_proof_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'rental_type' => RentalType::class,
            'battery_capacity_kwh' => 'float',
            'battery_left_kwh' => 'float',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'rental_fee' => 'integer',
            'charging_fee' => 'integer',
        ];
    }

    /**
     * Get the renter who made this rental.
     *
     * @return BelongsTo<User, $this>
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    /**
     * Get the rented garage.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Get the extensions of this rental.
     *
     * @return HasMany<RentalExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    /**
     * Get everything paid for this rental: rental fee, charging fee and all extension fees.
     *
     * Eager-load `extensions` when calling this on a list of rentals.
     *
     * @return int Amount in rupiah.
     */
    public function totalFee(): int
    {
        return $this->rental_fee + $this->charging_fee + $this->extensions->sum('fee');
    }

    /**
     * Get the status of this rental today (upcoming, active or finished).
     */
    public function status(): RentalStatus
    {
        return RentalStatus::forPeriod($this->start_date, $this->end_date, CarbonImmutable::today());
    }

    /**
     * Limit the query to rentals whose period overlaps the given period.
     *
     * @param  Builder<Rental>  $query
     * @param  CarbonImmutable  $start  First day of the period (inclusive).
     * @param  CarbonImmutable  $end  Last day of the period (inclusive).
     */
    public function scopeOverlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $query->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }
}
