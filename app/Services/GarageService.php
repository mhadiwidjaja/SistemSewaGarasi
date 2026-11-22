<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates garages in batches and gives each one its code: a type prefix and a 3-digit number
 * that continues from the garages of the same type created before (S001, S002, …).
 */
class GarageService
{
    /** Codes have 3 digits, so each type can have at most 999 garages. */
    public const MAX_GARAGES_PER_TYPE = 999;

    /** Number of digits after the type prefix in a garage code (S001). */
    public const CODE_DIGITS = 3;

    /**
     * Create a batch of garages of one type with the same prices.
     *
     * @param  GarageType  $type  Type of all garages in the batch.
     * @param  int  $quantity  Number of garages to create.
     * @param  int  $dailyPrice  Daily rental price in rupiah.
     * @param  int|null  $chargingPricePerKwh  Electricity price per kWh in rupiah; electric car garages only.
     * @return Collection<int, Garage> The created garages, in code order.
     *
     * @throws ValidationException When the batch would exceed 999 garages of this type.
     */
    public function createBatch(GarageType $type, int $quantity, int $dailyPrice, ?int $chargingPricePerKwh): Collection
    {
        return DB::transaction(function () use ($type, $quantity, $dailyPrice, $chargingPricePerKwh) {
            // Lock the existing garages of this type so two batches at the same moment cannot get the same codes.
            Garage::where('type', $type)->lockForUpdate()->get(['id']);
            $this->ensureCodesAvailable($type, $quantity);
            $firstNumber = $this->nextNumber($type);

            return collect(range($firstNumber, $firstNumber + $quantity - 1))
                ->map(fn (int $number) => Garage::create([
                    'code' => $this->formatCode($type, $number),
                    'type' => $type,
                    'daily_price' => $dailyPrice,
                    'charging_price_per_kwh' => $chargingPricePerKwh,
                ]));
        });
    }

    /**
     * Get the number the next garage of this type will get. Garages are never deleted,
     * so it is the current count plus one.
     */
    public function nextNumber(GarageType $type): int
    {
        return Garage::where('type', $type)->count() + 1;
    }

    /**
     * Get how many more garages of this type can still be created.
     */
    public function remainingCodes(GarageType $type): int
    {
        return self::MAX_GARAGES_PER_TYPE - $this->nextNumber($type) + 1;
    }

    /**
     * Build a garage code, e.g. Car + 10 → "C010".
     */
    private function formatCode(GarageType $type, int $number): string
    {
        return $type->codePrefix().str_pad((string) $number, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * @throws ValidationException
     */
    private function ensureCodesAvailable(GarageType $type, int $quantity): void
    {
        $remaining = $this->remainingCodes($type);

        if ($quantity > $remaining) {
            throw ValidationException::withMessages([
                'quantity' => "Jumlah melebihi batas. Sisa kode tersedia: {$remaining}.",
            ]);
        }
    }
}
