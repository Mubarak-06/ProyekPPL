# Akses administrator CV Karunia Abadi

Autentikasi admin menggunakan tabel `users`, guard `web`, dan form login yang
sama dengan pelanggan. Hak administrator ditentukan oleh kolom boolean
`is_admin`, bukan oleh nama atau domain email. Kolom ini tidak termasuk atribut
mass assignment; registrasi dan pembaruan profil pelanggan tidak dapat
memberikan hak admin.

## Menjalankan aplikasi

Jalankan MySQL XAMPP, lalu dari direktori proyek:

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

- Login: http://127.0.0.1:8000/login
- Dashboard admin: http://127.0.0.1:8000/admin/dashboard
- Email akun yang diminta: `admin@karuniaabadi.test`

Login admin selalu menuju dashboard. Login pelanggan tetap menggunakan tujuan
halaman pelanggan yang sebelumnya diminta, atau `/akun`. Tujuan admin yang
tersimpan sebelum login diabaikan untuk pelanggan. Pengunjung tanpa login
diarahkan ke `/login`; pelanggan yang mencoba mengakses endpoint admin menerima
HTTP 403. Seluruh endpoint admin, termasuk POST/PUT/PATCH/DELETE dan ekspor,
berada di grup middleware `auth` dan `PastikanAdmin`.

Tombol Keluar pada sidebar mengirim POST `/logout` dengan token CSRF. Logout
membatalkan sesi, meregenerasi token, dan mengarahkan admin ke login. Logout
pelanggan tetap menuju Beranda. Gunakan host/protokol yang sama sepanjang sesi
agar cookie dan token tetap sesuai; jangan mengecualikan `/logout` dari CSRF.

## Pembuatan akun pertama

Migrasi `2026_11_12_000001_add_is_admin_to_users.php` menambahkan boolean dengan
default `false` dan memeriksa keberadaan kolom terlebih dahulu. Pada instalasi
yang belum menjalankannya, tinjau koneksi database dan migrasi, lalu jalankan:

```sh
php artisan migrate --path=database/migrations/2026_11_12_000001_add_is_admin_to_users.php
```

Kemudian, dalam terminal interaktif lokal:

```sh
php artisan admin:buat admin@karuniaabadi.test
```

Perintah meminta password 12–255 karakter dan konfirmasinya melalui input
tersembunyi. Jika terminal tidak dapat menyembunyikan input, proses dibatalkan;
password tidak dialihkan menjadi input yang terlihat. Tidak ada opsi password
lewat argumen atau environment. Gunakan password unik yang kuat.

Akun bernama `Administrator CV Karunia Abadi` dibuat dengan `is_admin=true`.
Password di-hash sebelum query database. Perintah menolak email yang sudah ada
dan tidak mengubah password atau hak akses akun lama. Menjalankan ulang perintah
untuk email yang sama tidak membuat akun kedua. Jangan menjalankan seeder umum
atau reset database untuk membuat akun admin.

Perintah sukses akan menampilkan email dan URL login, tanpa password. Jika
proses dibatalkan sebelum selesai, akun belum dibuat; jalankan ulang perintah.
Password awal tidak tersedia di source atau dokumentasi ini.

## Pengujian

```sh
php vendor/bin/phpunit
```

Suite memakai SQLite dalam memori, bukan MySQL aktif. `AdminAuthTest` memeriksa
perlindungan seluruh route admin, login berdasarkan hak akses, penolakan
eskalasi hak lewat registrasi/profil, password salah, dan logout dengan CSRF
asli aktif. Tes integrasi pesanan lama kini menggunakan identitas admin untuk
operasi admin dan identitas pelanggan untuk membaca pesanan miliknya.

Hasil pengujian otomatis tidak berarti password akun nyata sudah diuji.
Setelah memasukkan password di terminal, buka `/login` dan masuk dengan email
admin tersebut, pastikan diarahkan ke dashboard, lalu gunakan tombol Keluar.
Jangan mengirim password atau token sesi melalui chat.

## Hasil penerapan lokal, 12 November 2026

- Migrasi penambahan kolom selesai pada `db_ppl` (batch 6).
- Akun `admin@karuniaabadi.test` sudah tersimpan dengan nama yang diminta,
  `is_admin=true`, dan password bcrypt. Password dimasukkan sendiri oleh
  pengelola melalui terminal tersembunyi.
- Total akun menjadi tiga: satu administrator dan dua pelanggan lama.
  Kedua akun pelanggan tetap `is_admin=false`; fingerprint seluruh kolom lama
  mereka sebelum dan sesudah migrasi/pembuatan admin sama.
- Seluruh 37 tes otomatis lulus, dengan 590 assertion.
- Pengujian tambahan pada MySQL menggunakan akun sementara dalam transaksi:
  login admin menuju dashboard (200), pelanggan ditolak dari dashboard (403),
  dan logout menggunakan CSRF berhasil untuk keduanya. Data akun sementara
  di-rollback. Sesi pengujian berada dalam memori.
- Permintaan HTTP nyata tanpa login ke `/admin/dashboard` diarahkan ke `/login`.
- Pengelola mengonfirmasi login akun admin nyata berhasil menuju dashboard.
- Halaman dashboard, controller pengelolaan, produk, pesanan, dan data pelanggan
  tidak didesain ulang atau dihapus. Perubahan tampilan admin hanya mengganti
  tautan Keluar dengan tombol POST yang mempertahankan gaya sidebar.

## File implementasi

- `database/migrations/2026_11_12_000001_add_is_admin_to_users.php`
- `app/Models/User.php`
- `app/Http/Middleware/PastikanAdmin.php`
- `app/Http/Controllers/Pelanggan/AuthController.php`
- `app/Console/Commands/BuatAdmin.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/admin/layouts/app.blade.php`
- `tests/Feature/AdminAuthTest.php`
- `tests/Feature/PelangganTest.php`
- `tests/PelangganTestCase.php`
- `docs/admin.md`
- `docs/autentikasi-pelanggan.md` (penanda bahwa audit lama mendahului
  implementasi autentikasi admin)
