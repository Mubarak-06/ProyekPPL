# Checklist pelanggan — Proyek PPL

Audit 82 halaman PDF. Halaman 55–82 hanya dipakai untuk memahami integrasi admin. Beranda mengikuti screenshot revisi terakhir.

| Halaman / kondisi | PDF (1-based) | Route | Blade di resources/views/pelanggan | Status audit awal | Pekerjaan saat audit |
|---|---|---|---|---|---|
| Beranda dan navigasi | 3, revisi screenshot | / | beranda | Sudah tersedia | Pertahankan revisi |
| Login kosong/terisi, saran input | 1–2, 4–8 | /login | auth/login | Sebagian | Toggle password, lupa password |
| Registrasi | Alur tombol 4–8 | /daftar | auth/daftar | Tersedia | Pertahankan validasi |
| Lupa/reset password | Tombol 4–8 | /lupa-password, /reset-password/{token} | auth/lupa-password, reset-password | Belum | Broker Laravel dan email |
| Modal berhasil ditambahkan | 9 | Kondisi session | partials/pesan | Sebagian | Dialog sukses |
| Tentang Kami | 10 | /tentang-kami | tentang | Sebagian | Keunggulan, kontak, jam buka |
| Keranjang | 11 | /keranjang | keranjang | Sebagian | Pilih item/semua, hapus terpilih, +/- |
| Profil Saya | 12 | /akun | akun/profil | Sebagian | Halaman baca terpisah |
| Edit profil | 26 | /akun/edit | akun/edit-profil | Belum | Telepon, unggah foto |
| Pesanan Saya | 13 | /pesanan | akun/pesanan | Sebagian | Cari, ukuran halaman, produk |
| Detail pesanan/status | 33, 35 | /pesanan/{id} | akun/detail-pesanan | Sebagian | Tabel dan informasi pengiriman; resi hanya jika ada |
| Alamat Saya | 14 | /alamat | akun/alamat | Sebagian | Hapus dengan konfirmasi |
| Tambah/edit alamat dan dropdown wilayah | 15–16, 27, 36 | /alamat/tambah, /alamat/{id}/edit | akun/form-alamat | Sebagian | Dropdown bertingkat |
| Katalog Baut | 17–25, 28–32 | /produk?kategori={id} | produk/index | Sebagian | Material, ukuran, kombinasi, kosong |
| Katalog Mur | 34, 37–39 | /produk?kategori={id} | produk/index | Sebagian | Filter yang sama |
| Katalog Ring | 40–43 | /produk?kategori={id} | produk/index | Sebagian | Filter yang sama |
| Katalog Sekrup | 44–47 | /produk?kategori={id} | produk/index | Sebagian | Filter yang sama |
| Katalog Alat Teknik | 48 | /produk?kategori={id} | produk/index | Sebagian | Judul kategori dari database |
| Detail produk | 49 | /produk/{id} | produk/show | Sebagian | Galeri dan +/- jumlah |
| Checkout | 50 | /checkout | checkout | Sebagian | Hanya item terpilih, edit alamat |
| Transfer bank, e-wallet, QRIS | 51–52, 54 | /pesanan/{id}/pembayaran | pembayaran | Belum terpisah | Instruksi, salin, unggah dan konfirmasi |
| Header/halaman tanpa isi | 53 | Layout bersama | layouts/app | Tersedia | Tidak membuat route kosong |

Dropdown dan form terisi memakai route halaman yang sama. Kondisi kosong memakai hasil database, tanpa data contoh. Status pembayaran mengikuti tabel pembayaran; unggah bukti tidak otomatis menjadikan pembayaran berhasil.

Kendala data audit: satu produk dengan gambar yang belum tersedia, belum ada pesanan, belum ada kolom nomor resi. SMTP belum disiapkan (mailer log). Nomor WhatsApp kemudian dikonfirmasi pengguna: **083821167555**.

## Status implementasi akhir

Semua halaman utama berikut sudah diimplementasikan. Variasi form kosong/terisi, dropdown, hasil filter, status pesanan, dan dialog menggunakan halaman yang sama, bukan route duplikat.

| Halaman | Route GET | File Blade (di resources/views/pelanggan) | Fungsi yang tersedia |
|---|---|---|---|
| Beranda | / | beranda.blade.php | Revisi screenshot terakhir, kategori database, rekomendasi tanpa klaim penjualan palsu |
| Semua Produk / lima kategori | /produk?kategori={id} | produk/index.blade.php | Pencarian nama/kode, kategori, material, ukuran, kombinasi, pagination dan hasil kosong |
| Detail produk | /produk/{id} | produk/show.blade.php | Galeri, spesifikasi, stok, jumlah +/−, beli dan tambah keranjang |
| Login | /login | auth/login.blade.php | Validasi, ingat saya, tampil/sembunyikan password, session diperbarui |
| Registrasi | /daftar | auth/daftar.blade.php | Email unik, konfirmasi dan hashing kata sandi |
| Lupa kata sandi | /lupa-password | auth/lupa-password.blade.php | Broker Laravel, respons tidak membocorkan keberadaan akun, pembatasan permintaan |
| Reset kata sandi | /reset-password/{token} | auth/reset-password.blade.php | Token sekali pakai, masa berlaku, konfirmasi kata sandi |
| Tentang Kami | /tentang-kami | tentang.blade.php | Foto toko, keunggulan, kontak, alamat dan jam operasional |
| Keranjang | /keranjang | keranjang.blade.php | Pilih item/semua, ubah jumlah, +/−, hapus item/terpilih, subtotal terpilih |
| Checkout | /checkout | checkout.blade.php | Login wajib, alamat milik sendiri, ongkir database, metode aktif dan siap digunakan |
| Pembayaran | /pesanan/{id}/pembayaran | pembayaran.blade.php | Instruksi bank/e-wallet/QRIS, salin nomor, unggah/seret bukti, status pemeriksaan |
| Profil Saya | /akun | akun/profil.blade.php | Nama, email, telepon dan tautan edit |
| Edit profil | /akun/edit | akun/edit-profil.blade.php | Ubah data, unggah dan pratinjau foto, validasi ukuran/jenis berkas |
| Pesanan Saya | /pesanan | akun/pesanan.blade.php | Cari kode, jumlah baris, pagination, produk dan status |
| Detail pesanan | /pesanan/{id} | akun/detail-pesanan.blade.php | Snapshot produk/harga, rincian total, alamat dan status/waktu pengiriman |
| Alamat Saya | /alamat | akun/alamat.blade.php | Daftar milik akun, edit dan hapus dengan modal konfirmasi |
| Tambah alamat | /alamat/tambah | akun/form-alamat.blade.php | Provinsi → kota → kecamatan → kelurahan dari database |
| Edit alamat | /alamat/{id}/edit | akun/form-alamat.blade.php | Wilayah terisi otomatis, validasi kepemilikan |

Endpoint pendukung: `GET /wilayah?jenis={kota|kecamatan|kelurahan}&induk={id}`, `POST /keranjang/pilihan`, `POST /logout`, `POST /pesanan/{id}/bukti`. Route mutasi lengkap tersedia di `routes/pelanggan.php` dan memakai middleware web/CSRF serta autentikasi untuk data akun.

## File dan perubahan

- Controller pelanggan: `ProdukController`, `AuthController`, `AkunController`, `KeranjangController`, dan `CheckoutController` di `app/Http/Controllers/Pelanggan`.
- Layout/partial pelanggan: `layouts/app`, `layouts/akun`, `layouts/auth`, `partials/pesan`, `partials/avatar`, `partials/produk`, `partials/gambar`, dan `partials/pagination`.
- Styling dan interaksi: `public/css/pelanggan.css`, `public/js/pelanggan.js`; tidak memuat CSS ini pada admin.
- Kontak toko: `config/toko.php`. WhatsApp berasal dari konfirmasi pengguna; telepon, email, Instagram, alamat dan jam buka mengikuti halaman 10 PDF.
- Migration `2026_11_11_000001_add_profil_pelanggan_to_users.php` menambahkan `nomor_telepon` dan `foto_profil` nullable. Migration ini sudah dijalankan di lokal; data lama tetap dipertahankan.
- `app/Models/Material.php` memiliki relasi produk. Terjemahan validasi/notifikasi di `lang/id/validation.php` dan `lang/id.json`.
- Tes integrasi tambahan: `tests/Feature/PelangganLanjutanTest.php`. Semua tes memakai SQLite sementara, bukan `db_ppl`.

## Pengujian dan integrasi

- Hasil akhir: **27 tes lolos, 246 assertion**. Pemeriksaan sintaks PHP, kompilasi Blade, dan `git diff --check` juga lolos.
- Pengujian server mencakup kepemilikan akun/alamat/pesanan, filter gabungan, reset kata sandi sekali pakai, file profil, alamat yang sudah dipakai, pemilihan produk checkout, stok, rollback, kirim ulang checkout, dan pembayaran.
- Pengujian browser menggunakan server Artisan dan database SQLite terpisah: login → profil/foto → tambah/edit/hapus alamat → filter → galeri/jumlah → pilih item keranjang → checkout → instruksi dan unggah bukti. Produk yang tidak dipilih tetap berada di keranjang.
- Halaman akun dan pembayaran diperiksa pada viewport 1280, 768, dan 390 px tanpa scroll horizontal atau error JavaScript.
- Bug jumlah form yang tersimpan sebagai string sudah diperbaiki, termasuk kompatibilitas session lama. Harga dan total dihitung ulang di server.
- Tanggal pelanggan ditampilkan dalam WITA melalui konfigurasi toko, tanpa mengubah zona waktu database atau tampilan admin.
- Hash semua controller/view admin dibandingkan sebelum/sesudah: tidak berubah. `.env` dan `routes/web.php` juga tidak berubah pada tahap ini.
- Data MySQL setelah pengujian tetap: 1 pengguna, 1 produk, 1 alamat, 0 pesanan, 0 pembayaran, 0 pengiriman. Data contoh hanya dipakai dalam lingkungan pengujian terpisah.

## Batas data dan konfigurasi

- SMTP belum dikonfigurasi: mailer lokal masih `log`. Tautan reset ditulis ke `storage/logs/laravel.log`, belum dikirim ke kotak masuk sungguhan.
- Pada data lokal, BCA nonaktif, DANA aktif, dan QRIS aktif tetapi berkas gambarnya belum tersedia. QRIS tidak dapat dipilih sebelum berkas valid tersedia. Permintaan checkout langsung untuk metode belum lengkap juga ditolak server.
- Foto produk asli yang dirujuk database belum ada, sehingga halaman memakai placeholder. Gambar kategori/banner tidak dipakai untuk mengganti foto produk database.
- Tabel pengiriman belum memiliki kolom resi. Status, waktu, penerima dan catatan ditampilkan sesuai data; tidak membuat nomor resi palsu.
- Unggah bukti hanya mengonfirmasi penerimaan berkas, bukan otomatis mengubah status menjadi Berhasil. Verifikasi pembayaran/gateway tidak dibuat menggantikan sistem admin; halaman pelanggan membaca status pembayaran yang tersimpan. Kode admin awal belum menyediakan aksi verifikasi bukti melalui route tersendiri.
- Route admin pada proyek awal belum memakai middleware autentikasi. Proteksi kepemilikan pada controller pelanggan tidak menggantikan pengamanan route admin. Pengaturan akses admin perlu ditangani terpisah tanpa mengasumsikan kolom role yang tidak ada.
- Email profil tetap bisa diperbarui dengan validasi email unik, mempertahankan fitur proyek sebelumnya; PDF menggambarkan email readonly.

## Menjalankan

Untuk workspace ini, konfigurasi database lama dipertahankan. Jalankan `php artisan serve` lalu buka alamat yang ditampilkan. CSS/JavaScript dimuat langsung dari `public`, sehingga tidak memerlukan build npm/Vite.

Pada checkout lain yang memakai database lama yang sama, jalankan migration profil secara khusus:

```powershell
php artisan migrate --path=database/migrations/2026_11_11_000001_add_profil_pelanggan_to_users.php
php artisan storage:link
php artisan serve
```

Jika link storage sudah ada, tidak perlu dibuat ulang. Jangan menjalankan migration yang menghapus data. Untuk pemeriksaan regresi, jalankan `php artisan test` (konfigurasi PHPUnit memakai SQLite dalam memori).
