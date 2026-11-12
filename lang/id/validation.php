<?php

return [
    'array' => ':attribute harus berupa daftar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'email' => 'Format :attribute tidak valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak sesuai.',
    'exists' => ':attribute yang dipilih tidak tersedia.',
    'regex' => 'Format :attribute tidak valid.',
    'image' => ':attribute harus berupa gambar.',
    'mimes' => ':attribute harus berupa file dengan format :values.',
    'uploaded' => ':attribute gagal diunggah.',
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
        'numeric' => ':attribute maksimal :max.',
        'file' => ':attribute maksimal :max KB.',
    ],
    'min' => [
        'string' => ':attribute minimal :min karakter.',
        'numeric' => ':attribute minimal :min.',
    ],
    'size' => ['string' => ':attribute harus berisi :size karakter.'],
    'attributes' => [
        'name' => 'Nama', 'email' => 'Email', 'password' => 'Kata sandi', 'jumlah' => 'Jumlah produk',
        'q' => 'Pencarian', 'kategori' => 'Kategori', 'nama_penerima' => 'Nama penerima',
        'nomor_telepon' => 'Nomor telepon', 'alamat_lengkap' => 'Alamat lengkap',
        'kelurahan_id' => 'Kelurahan', 'kode_pos' => 'Kode pos', 'alamat_id' => 'Alamat',
        'metode_pembayaran_id' => 'Metode pembayaran', 'catatan' => 'Catatan',
        'checkout_token' => 'Token checkout', 'bukti' => 'Bukti pembayaran',
        'foto_profil' => 'Foto profil', 'per_page' => 'Jumlah baris', 'pilihan' => 'Pilihan produk',
    ],
];
