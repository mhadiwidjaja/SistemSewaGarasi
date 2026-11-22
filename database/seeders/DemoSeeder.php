<?php

namespace Database\Seeders;

use App\Enums\GarageType;
use App\Enums\RegistrationStatus;
use App\Enums\RentalType;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageService;
use App\Services\RentalOrder;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Optional demo data for showing the application: renters in every registration state, garages of
 * every type, and rentals that are running, upcoming, finished and extended, spread over two years
 * for the reports. Dates are relative to today, so the demo always shows every state.
 *
 * Run on an empty database: php artisan migrate:fresh --seed --seeder=DemoSeeder
 * Everything goes through the application's services, so all codes, end dates and fees follow the real rules.
 */
class DemoSeeder extends Seeder
{
    /** Password of every demo renter account. */
    public const RENTER_PASSWORD = 'penyewa123';

    /** A 1×1 PNG used as every demo proof of payment. */
    private const PROOF_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC';

    /** Renters: [NIK, name, phone, email, registration status]. Only approved renters rent garages. */
    private const RENTERS = [
        'budi' => ['3578010101900001', 'Budi Santoso', '081234567801', 'budi@demo.test', RegistrationStatus::Approved],
        'siti' => ['3578010101900002', 'Siti Rahayu', '081234567802', 'siti@demo.test', RegistrationStatus::Approved],
        'andi' => ['3578010101900003', 'Andi Wijaya', '081234567803', 'andi@demo.test', RegistrationStatus::Approved],
        'dewi' => ['3578010101900004', 'Dewi Lestari', '081234567804', 'dewi@demo.test', RegistrationStatus::Approved],
        'rina' => ['3578010101900005', 'Rina Kusuma', '081234567805', 'rina@demo.test', RegistrationStatus::Pending],
        'agus' => ['3578010101900006', 'Agus Pratama', '081234567806', 'agus@demo.test', RegistrationStatus::Pending],
        'joko' => ['3578010101900007', 'Joko Susilo', '081234567807', 'joko@demo.test', RegistrationStatus::Rejected],
    ];

    private CarbonImmutable $today;

    private UploadedFile $proof;

    /** @var array<string, User> */
    private array $renters = [];

    /**
     * Seed the admin account and the demo data, unless demo data already exists.
     */
    public function run(GarageService $garageService, RentalService $rentalService): void
    {
        $this->call(DatabaseSeeder::class);

        if (User::where('role', UserRole::Renter)->exists()) {
            $this->command?->warn('Demo data dilewati: database sudah berisi penyewa. Jalankan migrate:fresh terlebih dahulu.');

            return;
        }

        $this->today = CarbonImmutable::today();
        $this->proof = $this->proofOfPayment();

        foreach (self::RENTERS as $key => $details) {
            $this->renters[$key] = $this->createRenter($details);
        }
        $this->createGarages($garageService);
        foreach ($this->rentals() as $rental) {
            $this->rent($rentalService, $rental);
        }
    }

    /**
     * 34 garages in four batches: more than one page in the garage list, and two different car prices.
     */
    private function createGarages(GarageService $garageService): void
    {
        $garageService->createBatch(GarageType::Bicycle, 10, 10_000, null);
        $garageService->createBatch(GarageType::Car, 12, 50_000, null);
        $garageService->createBatch(GarageType::Car, 6, 75_000, null);
        $garageService->createBatch(GarageType::ElectricCar, 6, 80_000, 2_500);
    }

    /**
     * The demo rentals. "start" is in days from today (negative = past); "battery" is [capacity, remaining] kWh;
     * "extend" adds one extension of that type (and days, for daily).
     *
     * @return list<array<string, mixed>>
     */
    private function rentals(): array
    {
        $avanza = ['vehicle' => VehicleType::Car, 'brand' => 'Toyota Avanza', 'plate' => 'B 1234 ABC'];
        $jazz = ['vehicle' => VehicleType::Car, 'brand' => 'Honda Jazz', 'plate' => 'B 2468 HJ'];
        $bicycle = ['vehicle' => VehicleType::Bicycle, 'brand' => 'Polygon Heist', 'plate' => null];

        return [
            // Running today: all four vehicle types, charging and no charging, one already extended.
            ['renter' => 'budi', 'garage' => 'C001', ...$avanza, 'type' => RentalType::Daily, 'start' => -2, 'days' => 5],
            ['renter' => 'siti', 'garage' => 'E001', 'vehicle' => VehicleType::ElectricCar, 'brand' => 'Hyundai Ioniq 5', 'plate' => 'B 5678 EV', 'battery' => [72.6, 20], 'type' => RentalType::Monthly, 'start' => -10],
            ['renter' => 'andi', 'garage' => 'S001', 'vehicle' => VehicleType::Motorcycle, 'brand' => 'Honda Beat', 'plate' => 'D 4321 XY', 'type' => RentalType::Daily, 'start' => 0, 'days' => 3],
            ['renter' => 'dewi', 'garage' => 'S002', ...$bicycle, 'type' => RentalType::Daily, 'start' => -1, 'days' => 7],
            ['renter' => 'budi', 'garage' => 'C002', 'vehicle' => VehicleType::ElectricCar, 'brand' => 'Wuling Air ev', 'plate' => 'B 999 WUL', 'battery' => [26.7, 10], 'type' => RentalType::Daily, 'start' => -1, 'days' => 2, 'extend' => [RentalType::Daily, 3]],

            // Upcoming.
            ['renter' => 'siti', 'garage' => 'C003', ...$jazz, 'type' => RentalType::Monthly, 'start' => 5],
            ['renter' => 'andi', 'garage' => 'S003', 'vehicle' => VehicleType::Bicycle, 'brand' => 'United Detroit', 'plate' => null, 'type' => RentalType::Daily, 'start' => 3, 'days' => 2],

            // Finished, over this year and last year (for the reports), two of them extended.
            ['renter' => 'andi', 'garage' => 'C007', 'vehicle' => VehicleType::Car, 'brand' => 'Suzuki Ertiga', 'plate' => 'D 1357 SE', 'type' => RentalType::Daily, 'start' => -7, 'days' => 2],
            ['renter' => 'siti', 'garage' => 'C005', ...$jazz, 'type' => RentalType::Daily, 'start' => -30, 'days' => 3],
            ['renter' => 'budi', 'garage' => 'C004', ...$avanza, 'type' => RentalType::Daily, 'start' => -60, 'days' => 10, 'extend' => [RentalType::Daily, 5]],
            ['renter' => 'dewi', 'garage' => 'S005', ...$bicycle, 'type' => RentalType::Daily, 'start' => -90, 'days' => 14],
            ['renter' => 'dewi', 'garage' => 'E002', 'vehicle' => VehicleType::ElectricCar, 'brand' => 'Tesla Model 3', 'plate' => 'L 3 TSL', 'battery' => [60, 15], 'type' => RentalType::Monthly, 'start' => -120, 'extend' => [RentalType::Monthly, null]],
            ['renter' => 'andi', 'garage' => 'S004', 'vehicle' => VehicleType::Motorcycle, 'brand' => 'Yamaha NMAX', 'plate' => 'D 8642 NM', 'type' => RentalType::Monthly, 'start' => -200],
            ['renter' => 'siti', 'garage' => 'C013', 'vehicle' => VehicleType::Car, 'brand' => 'Mazda CX-5', 'plate' => 'B 7777 MZ', 'type' => RentalType::Daily, 'start' => -330, 'days' => 6],
            ['renter' => 'budi', 'garage' => 'C006', ...$avanza, 'type' => RentalType::Monthly, 'start' => -400],
        ];
    }

    /**
     * Rent a garage (and optionally extend it) through the rental service, like a renter and the admin would.
     *
     * @param  array<string, mixed>  $rental  One entry of rentals().
     */
    private function rent(RentalService $rentalService, array $rental): void
    {
        $garage = Garage::where('code', $rental['garage'])->firstOrFail();
        $startDate = $this->today->addDays($rental['start']);

        $saved = $rentalService->createRental($this->renters[$rental['renter']], new RentalOrder(
            vehicleType: $rental['vehicle'],
            plateNumber: $rental['plate'],
            vehicleBrandType: $rental['brand'],
            batteryCapacityKwh: $rental['battery'][0] ?? null,
            batteryLeftKwh: $rental['battery'][1] ?? null,
            rentalType: $rental['type'],
            startDate: $startDate,
            days: $rental['days'] ?? null,
            garageType: $garage->type,
            garageId: $garage->id,
        ), $this->proof);

        // Booked on its start day (or today, for upcoming rentals), so the history order looks natural.
        $saved->forceFill(['created_at' => $startDate->min($this->today)])->save();

        if (isset($rental['extend'])) {
            [$type, $days] = $rental['extend'];
            $rentalService->extendRental($saved, $type, $days, $this->proof);
        }
    }

    /**
     * Create a renter with the given registration status.
     *
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: RegistrationStatus}  $details  One entry of RENTERS.
     */
    private function createRenter(array $details): User
    {
        [$nik, $name, $phone, $email, $status] = $details;

        $renter = new User(['nik' => $nik, 'name' => $name, 'phone' => $phone, 'email' => $email, 'password' => self::RENTER_PASSWORD]);
        $renter->role = UserRole::Renter;
        $renter->status = $status;
        $renter->save();

        return $renter;
    }

    /**
     * Write the placeholder image to a temporary file and wrap it as an upload.
     */
    private function proofOfPayment(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'demo-proof');
        file_put_contents($path, base64_decode(self::PROOF_PNG));

        return new UploadedFile($path, 'bukti-pembayaran.png', 'image/png', null, true);
    }
}
