# Pemeriksaan autentikasi pelanggan

> Catatan historis: audit di bawah dilakukan sebelum autentikasi admin
> ditambahkan. Implementasi sekarang menggunakan kolom `users.is_admin`, login
> bersama, dan middleware pelindung route admin. Lihat [panduan admin](admin.md).

## Hasil pemeriksaan lokal, 12 November 2026

- Registrasi: `POST /daftar`, login: `POST /login`, logout: `POST /logout`.
- Controller: `App\Http\Controllers\Pelanggan\AuthController`.
- Registrasi memakai `User::create`, login memakai `Auth::attempt`. Keduanya
  menggunakan koneksi default dan tabel `users`, melalui guard `web`, driver
  `session`, provider Eloquent `users`, model `App\Models\User`.
- Identitas login adalah **email**, bukan nama pengguna. Email registrasi wajib
  unik. Password registrasi minimal delapan karakter dan dikonfirmasi.
- Cast `password => hashed` pada model melakukan hashing. Provider Laravel
  memverifikasi password melalui hasher; tidak ada pembandingan teks biasa atau
  hashing ganda pada alur registrasi ini. Jangan hash ulang hash saat memindahkan
  data akun.
- Login dan registrasi meregenerasi session ID. Logout menghapus autentikasi,
  membatalkan sesi, dan meregenerasi token CSRF. Halaman akun memakai middleware
  `auth`; login/registrasi memakai `guest` dan pembatas percobaan.
- Login menuju URL pelanggan yang sebelumnya diminta, atau `/akun`. Registrasi
  menuju `/akun`; logout menuju Beranda. Kegagalan login menampilkan
  `Email atau kata sandi tidak sesuai.` untuk akun tidak ada maupun password
  salah, tanpa mengungkap keberadaan akun.
- Koneksi aktif lokal: MySQL/MariaDB `127.0.0.1:3306`, database `db_ppl`, dua
  baris `users`; kedua password teridentifikasi bcrypt. Kolom `users`, tabel
  `sessions`, dan seluruh migrasi yang tersedia sudah ada. Tidak ada cache
  konfigurasi aktif. Session memakai database, domain cookie `null`, dan
  `APP_KEY` tersedia. Nilai rahasia tidak dicetak.
- Log lokal pada 12 November 2026 pernah mencatat `SQLSTATE[HY000] [2002]`
  saat mengakses `sessions` di MySQL. Koneksi saat audit sudah berhasil.
  Catatan ini bukan bukti error di laptop teman.
- Pesan di laptop teman yang dilaporkan pengguna adalah `email atau password
  salah`. Pesan ini belum membedakan akun tidak ada dan password salah.
- Tidak ditemukan file `.sql` dalam workspace yang diperiksa. Isi SQL yang
  benar-benar dibagikan serta konfigurasi/data laptop teman belum dapat diperiksa.
- Dalam kode yang diperiksa tidak ada guard admin tersendiri di `config/auth.php`
  atau route login admin di `routes/web.php`. Audit ini tidak menambahkan atau
  mengubah autentikasi, route, maupun fitur admin.

**Kesimpulan:** alur autentikasi pelanggan lokal lolos pengujian. Penyebab pasti
di laptop teman belum dapat ditetapkan tanpa pesan error, konfigurasi efektif,
dan pemeriksaan akun pada database perangkat itu. Tidak diperlukan perubahan
controller, model, konfigurasi auth, atau struktur database berdasarkan bukti ini.

## Mengapa akun lokal bisa tidak tersedia di laptop lain

`DB_HOST=127.0.0.1` menunjuk ke komputer yang menjalankan PHP. Jika kedua laptop
menjalankan Laravel dan MySQL sendiri, masing-masing memiliki database terpisah.
Nama database yang sama tidak berarti server atau isinya sama. Menyalin source
code, menjalankan migrasi, atau menarik perubahan Git tidak menyalin akun yang
didaftarkan melalui website. SQL yang diekspor sebelum akun dibuat juga tidak
akan memuat akun tersebut. Hal ini perlu dikonfirmasi pada laptop teman.

Password bcrypt tidak terikat pada laptop atau `APP_KEY` yang dipakai saat
registrasi. Salinan baris akun dengan hash yang utuh dapat diverifikasi oleh
aplikasi yang kompatibel. Tidak perlu mengganti password akun atau menyamakan
`APP_KEY` dua instalasi independen untuk verifikasi password.

## Pemeriksaan pada masing-masing laptop

1. Jalankan MySQL XAMPP. Pastikan versi source dan `composer.lock` sama, lalu
   jalankan `composer install` bila dependensi belum sesuai.
2. Periksa `.env` secara lokal: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`,
   `DB_DATABASE=db_ppl`, kredensial database, dan kemungkinan `DB_URL` yang
   menimpa pengaturan koneksi. Jangan membagikan password database atau `APP_KEY`.
3. Jika `.env` diubah, jalankan `php artisan config:clear`, lalu mulai ulang
   server Laravel yang sedang berjalan. Periksa konfigurasi efektif, bukan hanya
   isi `.env`. Laravel yang memakai cache konfigurasi tidak memuat `.env` pada
   setiap request: https://laravel.com/docs/12.x/configuration#configuration-caching.
4. Buka `php artisan tinker`, lalu jalankan pemeriksaan baca saja berikut:

   ```php
   $c = DB::connection();
   [$c->getDriverName(), $c->getConfig('host'), $c->getConfig('port'), $c->getDatabaseName()];
   DB::selectOne('SELECT DATABASE() AS db');
   [config('auth.defaults.guard'), config('auth.guards.web.provider'), config('auth.providers.users.model')];
   Schema::hasTable('users');
   Schema::hasTable('sessions');
   App\Models\User::where('email', 'ganti-dengan-email-akun-yang-diuji')->exists();
   ```

   Bandingkan hasil untuk **email yang sama** pada kedua laptop. Jangan mencetak
   password, hash, isi sesi, atau seluruh model akun. Jumlah baris saja tidak
   membuktikan bahwa akun yang dimaksud ada. Pada proyek standar ini model `User`
   memakai koneksi default. Jika konfigurasi provider di laptop teman berbeda,
   telusuri model provider tersebut sebelum menyimpulkan hasilnya.
5. Jalankan `php artisan migrate:status` untuk membandingkan migrasi tanpa
   mengubah database. Jika ada kekurangan struktur, tinjau dan cadangkan terlebih
   dahulu; jangan menjalankan `migrate:fresh`, `db:wipe`, atau impor yang menimpa
   tabel berisi data.
6. Coba login memakai email terdaftar. Catat pesan dan status HTTP:

   | Gejala | Yang diperiksa |
   | --- | --- |
   | Email atau kata sandi tidak sesuai | Keberadaan email pada database aktif; jika ada, gunakan password yang benar atau alur reset resmi |
   | SQLSTATE 2002 | MySQL belum aktif, host/port salah, atau koneksi ditolak |
   | Tabel/kolom tidak ditemukan | Database tujuan dan status migrasi |
   | 419 | Token CSRF, cookie, URL yang dipakai, dan sesi; muat ulang formulir |
   | 429 | Pembatas percobaan login; tunggu masa pembatasan |
   | Kembali ke login setelah berhasil | Penyimpanan sesi, cookie domain/secure, dan konsistensi host/protokol |

   Karena `SESSION_DRIVER=database`, kegagalan mengakses tabel `sessions` bisa
   menggagalkan request sebelum verifikasi password. Gunakan host yang konsisten
   dalam satu sesi; `localhost`, `127.0.0.1`, dan IP LAN adalah host berbeda.
   `SESSION_DOMAIN=null` cocok untuk cookie host yang sedang diakses. Cookie
   `Secure` memerlukan HTTPS. Periksa log perangkat yang gagal, bukan hanya log
   laptop pengembang. Pastikan `APP_KEY` tersedia dan stabil; jangan mengganti
   key instalasi yang sudah berjalan untuk mencoba memperbaiki login.

## Agar kedua perangkat menggunakan akun yang sama

Pilihan paling langsung untuk pengembangan bersama adalah menjalankan **satu
aplikasi dan satu database**. Pada jaringan lokal tepercaya, jalankan:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

Teman membuka `http://IP-LAN-LAPTOP-SERVER:8000`, bukan `localhost` di laptopnya.
Sesuaikan izin firewall untuk jaringan lokal. MySQL tetap dapat menggunakan
`127.0.0.1` di laptop server; tidak perlu membuka port MySQL ke jaringan.
Keduanya mendaftar/login melalui aplikasi yang sama, dengan sesi browser
masing-masing. Berhenti menjalankan server ketika sesi pengembangan selesai.

Jika kedua laptop harus menjalankan Laravel sendiri, gunakan satu server
database bersama yang dapat dijangkau keduanya dengan akses database yang
dikonfigurasi untuk pengembangan. Jangan memakai `127.0.0.1` pada laptop teman
untuk menunjuk database laptop pengembang. Ini memerlukan penyiapan akses dan
jaringan; audit ini tidak mengubah konfigurasi jaringan atau `.env`.

Jika tetap memakai dua database lokal, data akun harus dipindahkan secara
terencana. Periksa dahulu apakah ekspor memuat **data** `users` dan akun yang
diperlukan, bukan hanya `CREATE TABLE`. Cadangkan kedua database dan gunakan
proses penggabungan yang tidak menimpa data lama, mempertimbangkan benturan ID,
email unik, dan relasi `user_id`. Pertahankan hash password apa adanya. Jangan
mengimpor sesi aktif atau menjalankan dump berisi `DROP TABLE` pada database
berisi data. Salinan sekali waktu ini tetap tidak menyinkronkan registrasi baru.

## Pengujian yang tersedia

Regresi terisolasi pada SQLite dalam memori:

```sh
php vendor/bin/phpunit --filter "test_akun_tersimpan_bisa_login_dari_sesi_baru|test_pelanggan_dapat_mendaftar_masuk_dan_keluar"
```

Selama audit juga dijalankan pengujian integrasi MySQL satu kali yang
dibatasi ke host lokal, database `db_ppl`, dan tabel `users` InnoDB.
Tes menggunakan route/controller asli untuk registrasi, penyimpanan akun,
verifikasi hash, login dari sesi baru, penolakan password salah, akses profil,
dan logout. Semua perubahan akun dilakukan di dalam transaksi yang di-rollback,
tanpa migrasi, seeder, reset database, penghapusan pelanggan, atau perubahan
password akun lama. Cache dan session pengujian memakai `array` agar tidak
mengganggu sesi pelanggan asli. Ini menguji penyimpanan akun MySQL tetapi tidak
menguji persistensi cookie browser atau session database lintas proses.

Hasil lokal: dua tes SQLite lulus (41 assertion), satu tes MySQL lulus
(36 assertion); jumlah akun MySQL kembali dua setelah rollback. Sesi baru dalam
pengujian mensimulasikan klien lain yang membaca database yang sama. Tes ini
**bukan** bukti login berhasil dari laptop teman. Pemeriksaan database dan uji
login nyata di perangkat tersebut masih diperlukan.
