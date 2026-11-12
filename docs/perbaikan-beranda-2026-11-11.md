# Perbaikan Beranda berdasarkan referensi Figma

> Pembaruan: MySQL sudah tersedia dan verifikasi dengan data asli telah selesai.
> Lihat [laporan live terbaru](perbandingan-beranda-live.md). Keterbatasan MySQL di
> bawah merupakan catatan sesi sebelumnya.

Referensi: `public/images/pelanggan/referensi-beranda.png` (1280 × 1229).
Perubahan produksi hanya pada `public/css/pelanggan.css`. Route `/` tetap memakai
`ProdukController::beranda`, Blade `pelanggan.beranda`, dan stylesheet pelanggan
yang dimuat layout dengan hash file untuk pembaruan cache.

## Perubahan

Aturan tinggi berdasarkan `dvh` membuat ukuran berubah mengikuti tinggi layar.
Aturan tersebut diganti langsung pada selector asal, bukan dengan override baru.
Banner memakai tinggi 328px, judul 40px, dan tombol 158 × 43px. Gambar potongan
referensi yang sudah ada tetap digunakan, dengan area kanan selebar 50% banner.
Kategori memakai lima kolom desktop, gap 20px, kartu tinggi 200px, dan tombol 38px.
Kartu produk memakai lima kolom, tinggi minimum 295px, harga 18px, dan tombol 35px.
Satu produk tetap hanya mengisi satu kolom. Tidak ada perubahan query atau data.
Heading/badge tetap menggunakan mekanisme penjualan yang sudah ada: tanpa bukti
penjualan, tampil sebagai Pilihan Produk.

## Pengukuran browser final

Chrome headless, viewport 1280 × 720, device scale factor 1, zoom 100%.
Scrollbar disembunyikan oleh opsi browser untuk membandingkan bidang gambar
1280px penuh; CSS aplikasi tidak menyembunyikan scrollbar atau konten halaman.

| Komponen | Estimasi referensi | Pratinjau setelah perubahan |
| --- | --- | --- |
| Navbar termasuk border header | 75px | 75px |
| Hero (x, y) | (32, 111) | (32, 111) |
| Hero | 1216 × 328px | 1216 × 328px |
| Kategori (x, y) | (32, 545) | (32, 545) |
| Kartu kategori | sekitar 228 × 200px | 227,19 × 200px |
| Awal grid produk | sekitar y=870 | y=870 (keadaan kosong) |

Pemeriksaan tambahan pada lebar 1024px dan 390px: tidak ada overflow horizontal
atau isi kartu kategori yang keluar dari kartu. CSS terbaru berhasil dimuat.
Pratinjau setelah perubahan diperiksa secara visual terhadap PNG referensi.
Banner dan kategori mendekati ukuran referensi; font dan ikon bukan aset node
Figma asli sehingga masih mungkin memiliki perbedaan bentuk kecil.

## Bukti dan keterbatasan

- [Referensi Figma](../public/images/pelanggan/referensi-beranda.png).
- [Arsip tampilan sebelum perbaikan](../storage/app/referensi/admin-beranda-aset-final-beranda-1280-720.png).
  Ini arsip yang sudah tersedia sebelum sesi ini, bukan tangkapan halaman live
  baru. Arsip menampilkan produk yang tersedia saat arsip tersebut dibuat.
- [Pratinjau setelah perbaikan, 1280px](../storage/app/referensi/beranda-pratinjau-sesudah-1280.png).
- [Pratinjau setelah perbaikan, 390px](../storage/app/referensi/beranda-pratinjau-sesudah-390.png).
- [Pengukuran browser](../storage/app/referensi/beranda-pratinjau-pengukuran.json).

Halaman live dicoba melalui server Laravel, tetapi mengembalikan HTTP 500:
MySQL 127.0.0.1:3306 menolak koneksi (SQLSTATE HY000/2002), termasuk setelah
server dijalankan di luar sandbox. Database dan `.env` tidak diubah.

Screenshot sesudah adalah Blade asli yang dirender terpisah tanpa akses database,
dengan lima nama kategori referensi dan koleksi produk kosong. Tidak dibuat produk
atau harga pengganti. Karena itu screenshot sebelum/sesudah tidak membandingkan
data produk yang sama dan belum membuktikan hasil kartu produk dengan MySQL live.
Ukuran kartu produk masih perlu diperiksa kembali setelah MySQL tersedia.

Pengujian: 13 tes PelangganTest lulus (136 assertions), menggunakan SQLite dalam
memori sesuai konfigurasi pengujian yang sudah ada. Kompilasi Blade juga berhasil.
Tidak ada perubahan pada admin, controller, route, atau database db_ppl.

Referensi setinggi 1229px tidak bisa ditampilkan seluruhnya pada viewport laptop
720px dengan ukuran yang sama. Scroll vertikal dipertahankan agar keterbacaan dan
proporsi desain tetap sesuai, tanpa zoom atau pemotongan konten.
