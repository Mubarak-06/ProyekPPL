<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class BuatAdmin extends Command
{
    protected $signature = 'admin:buat {email : Email unik akun administrator}';

    protected $description = 'Membuat akun administrator dengan password tersembunyi, tanpa mengubah akun lama';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan di terminal interaktif; password tidak diterima melalui argumen.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', 'is_admin')) {
            $this->error('Kolom is_admin belum tersedia. Jalankan migrasi admin terlebih dahulu.');

            return self::FAILURE;
        }

        $email = trim((string) $this->argument('email'));
        $validator = Validator::make(['email' => $email], ['email' => 'required|email|max:255|unique:users,email']);
        if ($validator->fails()) {
            $this->error('Email tidak valid atau sudah digunakan. Tidak ada akun yang diubah.');

            return self::FAILURE;
        }

        // Fallback echo dimatikan: batalkan jika terminal tidak mendukung input tersembunyi.
        $password = $this->secret('Password admin (minimal 12 karakter)', false);
        $confirmation = $this->secret('Ulangi password admin', false);
        if (! is_string($password) || mb_strlen($password) < 12 || mb_strlen($password) > 255
            || ! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->error('Password harus 12–255 karakter dan konfirmasi harus sama. Akun belum dibuat.');

            return self::FAILURE;
        }

        // Hash dibuat sebelum query; password asli tidak masuk binding SQL atau output.
        $hash = Hash::make($password);
        unset($password, $confirmation);

        try {
            $user = new User(['name' => 'Administrator CV Karunia Abadi', 'email' => $email]);
            $user->password = $hash;
            $user->is_admin = true;
            $user->save();
        } catch (QueryException) {
            $this->error('Akun gagal disimpan. Periksa koneksi atau email yang sudah digunakan; akun lama tidak diubah.');

            return self::FAILURE;
        }

        $this->info('Akun administrator berhasil dibuat: '.$email);
        $this->line('Masuk melalui '.route('login'));

        return self::SUCCESS;
    }
}
