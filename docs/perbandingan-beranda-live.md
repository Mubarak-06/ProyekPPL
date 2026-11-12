# Perbandingan Beranda dengan data MySQL asli

Verifikasi ini menggantikan keterbatasan pratinjau kosong pada laporan sebelumnya.
MySQL db_ppl sudah tersedia. Halaman `/` dijalankan melalui Laravel pada
http://127.0.0.1:8001 dan diperiksa dengan Chrome pada viewport 1280 × 720 serta
390 × 844, device scale factor 1, zoom 100%. Screenshot penuh diambil pada kedua
ukuran. Hash stylesheet yang dimuat diperiksa terhadap file CSS di disk.

## Hasil visual

| Elemen | Referensi PNG, estimasi | Hasil website pada lebar 1280px |
| --- | --- | --- |
| Tinggi header | 75px | 75px termasuk border |
| Kolom pencarian | x=601, lebar 476px | x=601,36, lebar 474,95px |
| Hero | x=32, y=111; 1216 × 328px | x=32, y=111; 1216 × 328px |
| Kartu kategori pertama | x=32, y=545; sekitar 228 × 200px | x=32, y=545; 227,19 × 200px |
| Kartu produk pertama | x=32, y=870; sekitar 228 × 295px | x=32, y=870; 227,19 × 295px |
| Tombol beli | y=1073; tinggi 35px | y=1073; tinggi 35px |

Screenshot final telah dilihat dan dibandingkan dengan referensi. Penyempurnaan
sesi ini meliputi posisi dan lebar pencarian, font pencarian 14px dengan bobot
normal, judul hero 41px, area gambar kategori 76px, serta posisi tombol produk.
Semua aturan diedit pada selector asal di `public/css/pelanggan.css`.

- [Referensi Figma](../public/images/pelanggan/referensi-beranda.png)
- [Sebelum penyempurnaan sesi ini, live 1280px](../storage/app/referensi/beranda-live-sebelum-1280.png)
- [Sesudah penyempurnaan, live 1280px](../storage/app/referensi/beranda-live-sesudah-1280.png)
- [Sesudah penyempurnaan, live 390px](../storage/app/referensi/beranda-live-sesudah-390.png)
- [Pengukuran sebelum](../storage/app/referensi/beranda-live-sebelum.json)
- [Pengukuran sesudah](../storage/app/referensi/beranda-live-sesudah.json)

Screenshot sebelum adalah kondisi awal sesi lanjutan ini, setelah perbaikan ukuran
utama pada sesi sebelumnya. Perubahan sesi ini lebih kecil karena geometri utama
sudah sesuai referensi.

## Data dan batasan

Database menampilkan satu produk, Baut Hexagonal M8, Rp2.000. Produk tersebut tetap
mengisi satu kolom desktop. Gambar produk belum tersedia sehingga placeholder
asli tetap ditampilkan. Tidak ada penggantian dengan foto produk M10 dari Figma.
Tidak ada penjualan yang mendukung label Terlaris, sehingga mekanisme yang sudah
ada tetap menampilkan Pilihan Produk. Perbedaan ini disengaja agar informasi
produk sesuai database. Tidak ditambahkan produk atau harga pengganti.

Tidak ditemukan overflow horizontal maupun isi kartu keluar dari kartu pada kedua
viewport. Referensi setinggi 1229px tetap memerlukan scroll pada viewport 720px;
konten tidak dipotong dan skala halaman tidak diubah. Font/ikon website dan efek
warna raster referensi masih dapat memiliki perbedaan kecil.

13 tes PelangganTest lulus, 136 assertions, menggunakan SQLite dalam memori.
Admin, route, controller, konfigurasi database, serta data produk db_ppl tidak
diubah. Script screenshot hanya membuka halaman dengan GET dan tidak menjalankan
pembelian atau menambah isi keranjang.
