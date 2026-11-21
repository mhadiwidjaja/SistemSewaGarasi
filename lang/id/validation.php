<?php

/*
 * Indonesian validation messages. Only the rules used by this application are listed;
 * anything missing falls back to Laravel's built-in English messages.
 */
return [
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'digits' => ':Attribute harus :digits digit angka.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',

    'attributes' => [
        'charging_price_per_kwh' => 'harga pengisian daya per kWh',
        'daily_price' => 'harga sewa harian',
        'email' => 'email',
        'name' => 'nama lengkap',
        'nik' => 'NIK',
        'password' => 'kata sandi',
        'phone' => 'nomor telepon',
        'quantity' => 'jumlah garasi',
        'type' => 'jenis garasi',
    ],
];
