# Tentang Kami: perbandingan dengan Figma

Referensi utama: `public/images/pelanggan/referensi-tentang.png`, salinan screenshot
asli pengguna (1280 × 1031). Halaman tetap memakai route `/tentang-kami` dan Blade
yang sudah ada; tidak dibuat route atau halaman duplikat.

## File implementasi

- `resources/views/pelanggan/tentang.blade.php`: breadcrumb, foto asli dari area
  screenshot, deskripsi lengkap, empat keunggulan, kontak, serta format tampilan
  jam operasional. Foto ditampilkan melalui viewport SVG yang membatasi area
  foto asli (65,196,460,344); tidak dibuat atau diganti dengan foto bangunan lain.
- `public/css/pelanggan.css`: aturan Tentang Kami diperbaiki pada selector asal,
  dengan penyesuaian navbar yang dibatasi kelas body `.tentang`.
- `resources/views/pelanggan/layouts/app.blade.php`: ikon panah Produk serta akun
  khusus route Tentang Kami. Tautan dan perilaku navbar halaman lain dipertahankan.
- `public/images/pelanggan/referensi-tentang.png`: aset referensi foto asli.

## Ukuran pada viewport 1280 × 1031

| Komponen | Target dari screenshot, estimasi | Hasil browser |
| --- | --- | --- |
| Kotak profil, posisi | x32, y149 | x32, y149 |
| Kotak profil, ukuran | 1216 × 439px | 1216 × 438,56px |
| Foto, posisi | x65, y196 | x65, y196,28 |
| Foto, ukuran | 460 × 344px | 460 × 344px |
| Kotak kontak, posisi | x32, y612 | x32, y611,56 |
| Kotak kontak, ukuran | 1216 × 355px | 1216 × 355px |
| Garis di atas alamat/jam | y802 | y801,56 |

Screenshot final diperiksa secara visual terhadap referensi. Bentuk ikon memakai
Bootstrap Icons yang sudah ada, sehingga memiliki sedikit perbedaan dengan ikon
di screenshot. WhatsApp mengikuti konfigurasi resmi `083821167555`, bukan nomor
contoh Figma `0812 3456 7890`. Data kontak dan jam di konfigurasi tidak diubah.

## Pengujian dan bukti

- Chrome, zoom 100%, device scale factor 1; viewport 1280 × 1031 dan 390 × 844.
- CSS terbaru terverifikasi melalui hash stylesheet yang dimuat.
- Lebar dokumen sesuai viewport, tanpa overflow horizontal.
- Foto dan deskripsi ditumpuk vertikal di ponsel; keunggulan dua kolom, kontak
  satu kolom; tidak ada footer pelanggan.
- Screenshot Beranda, Semua Produk, dan dashboard admin sebelum/sesudah identik
  secara byte pada viewport 1280 × 1031.
- 25 pengujian pelanggan lulus, 243 assertions, memakai SQLite dalam memori.
  Kompilasi Blade berhasil.
- Database MySQL db_ppl dan kode admin tidak diubah.

[Screenshot sebelum desktop](../storage/app/referensi/tentang-live-sebelum-1280.png)

[Screenshot sesudah desktop](../storage/app/referensi/tentang-live-sesudah-1280.png)

[Screenshot sesudah ponsel](../storage/app/referensi/tentang-live-sesudah-390.png)

[Pengukuran final](../storage/app/referensi/tentang-live-sesudah.json)
