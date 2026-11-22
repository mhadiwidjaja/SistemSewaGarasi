<?php

namespace App\Models;

use App\Enums\GarageType;
use App\Services\RentalCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A rentable garage with its own code (S001, C010, E003) and prices.
 *
 * A garage has no stored status: it is "used" on a day when one of its rentals covers that day.
 */
class Garage extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'type',
        'daily_price',
        'charging_price_per_kwh',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'daily_price' => 'integer',
            'charging_price_per_kwh' => 'integer',
        ];
    }

    /**
     * Get all rentals of this garage.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Get the monthly rental price (25 × daily price).
     *
     * @return int Price in rupiah.
     */
    public function monthlyPrice(): int
    {
        return app(RentalCalculator::class)->monthlyPrice($this->daily_price);
    }

    /**
     * Determine whether no rental of this garage overlaps the given period.
     *
     * @param  CarbonImmutable  $start  First day of the period (inclusive).
     * @param  CarbonImmutable  $end  Last day of the period (inclusive).
     */
    public function isAvailableBetween(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return ! $this->rentals()->overlapping($start, $end)->exists();
    }

    /**
     * Get the rental that occupies this garage today, if any ("used" status).
     *
     * @return HasOne<Rental, $this>
     */
    public function currentRental(): HasOne
    {
        return $this->hasOne(Rental::class)->activeOn(CarbonImmutable::today());
    }

    /**
     * Limit the query to garages with no rental overlapping the given period.
     *
     * @param  Builder<Garage>  $query
     * @param  CarbonImmutable  $start  First day of the period (inclusive).
     * @param  CarbonImmutable  $end  Last day of the period (inclusive).
     */
    public function scopeAvailableBetween(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $query->whereDoesntHave('rentals', fn (Builder $rentals) => $rentals->overlapping($start, $end));
    }
}
