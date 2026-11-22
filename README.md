# Sistem Sewa Garasi

Garage rental web application (Laravel 12, MySQL, Bootstrap 5) with an admin and a renter role. The interface is in Bahasa Indonesia.

## Installation

Requires PHP 8.2+, Composer and a running MySQL/MariaDB server (e.g. XAMPP).

```bash
git clone https://github.com/mhadiwidjaja/SistemSewaGarasi.git
cd SistemSewaGarasi
composer setup
php artisan serve
```

`composer setup` installs the dependencies, creates `.env` and the `sewa_garasi` database (user `root`, no password; change `DB_*` in `.env` if needed), and adds the admin account. Then open http://localhost:8000.

**Admin:** `admin@sewagarasi.test` / `admin123`

## Demo data (optional)

```bash
php artisan migrate:fresh --seed --seeder=DemoSeeder
```

Replaces the data with renters in every state, 34 garages and rentals that are running, upcoming, finished and extended. Renter password: `penyewa123`.
- Approved: `budi@demo.test`, `siti@demo.test`, `andi@demo.test`, `dewi@demo.test`
- Waiting for verification: `rina@demo.test`, `agus@demo.test`
- Rejected: `joko@demo.test`

## Tests

```bash
php artisan test
```
