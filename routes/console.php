<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\Console\Command\Command;
use App\Models\User;
use App\Services\AlurPengiriman;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('midtrans:sync-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Artisan::command('kurir:buat', function () {
    if (! AlurPengiriman::tersedia()) {
        $this->error('Skema pengiriman toko belum tersedia.');

        return Command::FAILURE;
    }
    $admin = User::where('is_admin', true)->where('email', (string) $this->ask('Email admin yang mengotorisasi'))->first();
    $sandiAdmin = (string) $this->secret('Kata sandi admin');
    if (! $admin || ! Hash::check($sandiAdmin, $admin->password)) {
        $this->error('Otorisasi admin gagal.');

        return Command::FAILURE;
    }
    if (User::where('slot_kurir', 1)->exists()) {
        $this->error('Satu akun kurir sudah tersedia.');

        return Command::FAILURE;
    }
    $data = [
        'name' => $this->ask('Nama kurir'),
        'email' => $this->ask('Email baru untuk kurir'),
        'password' => $this->secret('Kata sandi kurir'),
        'password_confirmation' => $this->secret('Ulangi kata sandi kurir'),
    ];
    $validator = Validator::make($data, [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:12|max:72|confirmed',
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $pesan) {
            $this->error($pesan);
        }

        return Command::FAILURE;
    }
    try {
        (new User($validator->validated()))->forceFill(['slot_kurir' => 1, 'is_admin' => false])->save();
    } catch (UniqueConstraintViolationException) {
        $this->error('Email atau slot kurir sudah digunakan.');

        return Command::FAILURE;
    }
    $this->info('Akun kurir berhasil dibuat.');

    return Command::SUCCESS;
})->purpose('Membuat satu akun kurir dengan otorisasi admin');
