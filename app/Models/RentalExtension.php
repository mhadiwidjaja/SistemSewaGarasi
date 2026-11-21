<?php

namespace App\Models;

use App\Enums\RentalType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An extension of a rental made by the admin. It starts the day after the previous end date.
 */
class RentalExtension extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'rental_type',
        'previous_end_date',
        'new_end_date',
        'fee',
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
            'rental_type' => RentalType::class,
            'previous_end_date' => 'immutable_date',
            'new_end_date' => 'immutable_date',
            'fee' => 'integer',
        ];
    }

    /**
     * Get the extended rental.
     *
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
