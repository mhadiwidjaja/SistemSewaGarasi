<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\RentalStatus;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The optional demo data covers every feature and state, and is only added to an empty database.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Prepare the data shared by the tests in this class. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-22 09:00:00');
        Storage::fake('local');
    }

    /** Demo data contains every registration state, garage type and rental state. */
    public function test_demo_data_contains_every_state(): void
    {
        $this->seed(DemoSeeder::class);

        $renterStatuses = User::where('role', 'renter')->get()->countBy(fn (User $user) => $user->status->value);
        $this->assertSame(4, $renterStatuses[RegistrationStatus::Approved->value]);
        $this->assertSame(2, $renterStatuses[RegistrationStatus::Pending->value]);
        $this->assertSame(1, $renterStatuses[RegistrationStatus::Rejected->value]);

        $this->assertSame(34, Garage::count());
        $this->assertTrue(Garage::whereIn('code', ['S010', 'C018', 'E006'])->count() === 3);

        $rentalStatuses = Rental::all()->countBy(fn (Rental $rental) => $rental->status()->value);
        $this->assertSame(5, $rentalStatuses[RentalStatus::Active->value]);
        $this->assertSame(2, $rentalStatuses[RentalStatus::Upcoming->value]);
        $this->assertSame(8, $rentalStatuses[RentalStatus::Finished->value]);

        $this->assertSame(3, RentalExtension::count());
        $this->assertGreaterThan(0, Rental::whereRelation('garage', 'code', 'E001')->sole()->charging_fee);
        $this->assertSame(0, Rental::whereRelation('garage', 'code', 'C002')->sole()->charging_fee);
    }

    /** Demo renters can log in with the documented password. */
    public function test_demo_renter_can_log_in(): void
    {
        $this->seed(DemoSeeder::class);

        $this->post('/login', ['email' => 'budi@demo.test', 'password' => DemoSeeder::RENTER_PASSWORD])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    /** Running the demo seeder on a database that already has renters adds nothing. */
    public function test_demo_data_is_only_added_to_an_empty_database(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(7, User::where('role', 'renter')->count());
        $this->assertSame(15, Rental::count());
    }
}
