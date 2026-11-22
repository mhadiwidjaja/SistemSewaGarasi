<?php

/*
 * Indonesian validation messages. Only the rules used by this application are listed;
 * anything missing falls back to Laravel's built-in English messages.
 */
return [
    'after_or_equal' => ':Attribute tidak boleh sebelum :date.',
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date_format' => ':Attribute harus berupa tanggal yang valid.',
    'digits' => ':Attribute harus :digits digit angka.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'gt' => [
        'numeric' => ':Attribute harus lebih dari :value.',
    ],
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'lte' => [
        'numeric' => ':Attribute tidak boleh lebih dari :value.',
    ],
    'max' => [
        'file' => ':Attribute maksimal :max kilobyte.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'mimes' => ':Attribute harus berupa file: :values.',
    'numeric' => ':Attribute harus berupa angka.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',

    'attributes' => [
        'battery_capacity_kwh' => 'kapasitas baterai',
        'battery_left_kwh' => 'sisa baterai',
        'charging_price_per_kwh' => 'harga pengisian daya per kWh',
        'daily_price' => 'harga sewa harian',
        'days' => 'jumlah hari',
        'email' => 'email',
        'garage_id' => 'garasi',
        'garage_type' => 'jenis garasi',
        'month' => 'bulan',
        'name' => 'nama lengkap',
        'nik' => 'NIK',
        'password' => 'kata sandi',
        'payment_proof' => 'bukti pembayaran',
        'phone' => 'nomor telepon',
        'plate_number' => 'nomor polisi',
        'quantity' => 'jumlah garasi',
        'rental_type' => 'jenis sewa',
        'start_date' => 'tanggal mulai',
        'type' => 'jenis garasi',
        'vehicle_brand_type' => 'merek & tipe',
        'vehicle_type' => 'jenis kendaraan',
        'year' => 'tahun',
    ],
];
