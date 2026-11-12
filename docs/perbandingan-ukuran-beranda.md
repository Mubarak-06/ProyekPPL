# Perbandingan ukuran Beranda dengan referensi Figma

Referensi: PNG 1280 x 1229 yang ditemukan di images/e9530a1be21f5ebfeb64d130f7f9a219f3444014 dalam Proyek PPL (1).fig. Ukuran referensi berikut merupakan estimasi dari piksel PNG, bukan properti node Figma. Ukuran website diukur menggunakan getBoundingClientRect dan getComputedStyle pada viewport 1280 x 1229, device scale factor 1, zoom 100%, setelah font dan gambar dimuat.

## Geometri

| Komponen | Referensi PNG (estimasi) | Sebelum | Sesudah |
|---|---|---|---|
| Navbar | 1280 x 75px | 1280.0 x 56.0px | 1280.0 x 75.0px |
| Kolom pencarian | 476 x 38px | 560.3 x 42.0px | 485.0 x 38.0px |
| Hero | 1216 x 328px | 1232.0 x 220.0px | 1216.0 x 328.0px |
| Tombol hero | 158 x 43px | 150.0 x 36.0px | 158.0 x 43.0px |
| Kartu kategori | 228 x 200px | 233.6 x 135.0px | 227.2 x 200.0px |
| Tombol kategori | 187 x 38px | 207.6 x 30.0px | 185.2 x 38.0px |
| Kartu produk | 228 x 295px | 233.6 x 242.2px | 227.2 x 294.1px |
| Tombol produk | 189 x 35px | 207.6 x 30.0px | 187.2 x 35.0px |
| Badge produk | sekitar 80 x 23px; teks referensi lebih pendek | 103.5 x 20.0px | 111.8 x 21.6px |

## Tipografi

| Komponen | Referensi PNG (estimasi font) | Sebelum | Sesudah |
|---|---|---|---|
| Logo | 24px | 23px | 24px |
| Menu | 15px | 15px | 15px |
| Judul hero | 40px | 34px | 40px |
| Deskripsi hero | 16px | 15px | 16px |
| Tombol hero | 14px | 13px | 14px |
| Heading kategori | 30px | 22px | 30px |
| Nama kategori | 21px | 16px | 21px |
| Tombol kategori | 13px | 12px | 13px |
| Heading produk | 30px | 22px | 30px |
| Badge produk | 11px | 10px | 11px |
| Nama produk | 15px | 13px | 15px |
| Harga | 18px | 16px | 18px |
| Tombol produk | 13px | 12px | 13px |

## Posisi dan jarak

| Komponen | Target PNG (estimasi) | Sebelum | Sesudah |
|---|---|---|---|
| Hero, koordinat kiri atas | (32, 111) | (24, 67.0) | (32, 111.0) |
| Kartu kategori, koordinat kiri atas | (32, 545) | (24, 337.4) | (32, 545.0) |
| Kartu produk, koordinat kiri atas | (32, 870) | (24, 525.2) | (32, 870.0) |

Margin horizontal 32px; jarak grid 20px; banner ke heading kategori 35px; heading kategori ke grid 32px; kategori ke heading produk 56px. Padding hero 48px di kiri/kanan. Radius hero 16px, kartu 18px, tombol 8px. Judul hero memakai bobot 800 dan line-height 1.02; nama produk 700 dan line-height 1.3. Selisih titik tengah teks dan banner terukur 0px.

## Hasil dan batas perbandingan

- Kedua media query pemadatan desktop dihapus. Tinggi desktop tidak lagi bergantung pada tinggi viewport.
- Satu produk tetap menempati satu dari lima kolom. Tidak ada produk tambahan atau perubahan database.
- Gambar banner yang tersedia berbeda dari screenshot Figma. Kumpulan aset .fig hanya menyertakan banner tersebut di dalam screenshot halaman lengkap, bukan sebagai gambar banner terpisah. Gambar website dipertahankan, memakai contain dan posisi kanan tengah agar baut/mur lebih kecil dan tidak terpotong oleh cover. Komposisi aset belum identik.
- Aset Sekrup dan Alat Teknik yang tersedia juga berbeda dari screenshot: satu sekrup dan kunci, sedangkan referensi menampilkan pasangan alat. Aset tetap dipertahankan; tidak dibuat gambar palsu.
- Ukuran area gambar kategori 180 x 80px; objek di dalamnya lebih kecil karena whitespace bawaan PNG. Tinggi area foto produk 103px; file gambar M8 tidak ditemukan, sehingga placeholder dipertahankan.
- Badge lebih lebar karena memakai teks Pilihan Produk, sedangkan referensi memakai Terlaris. Lebar mengikuti teks; tidak dipotong.
- Footer yang sudah ada dipertahankan dengan tinggi 32px dan jarak sekitar 33px dari kartu produk. Referensi tidak menunjukkan footer yang sama.
- Screenshot final: storage/app/referensi/ukuran-figma-sesudah.png. Target asli: storage/app/referensi/figma-target-1280-1229.png. Data lengkap sebelum/sesudah disimpan di ukuran-figma-sebelum.json dan ukuran-figma-sesudah.json.
- File implementasi yang diubah hanya public/css/pelanggan.css; admin, route, controller, dan database tidak diubah.
