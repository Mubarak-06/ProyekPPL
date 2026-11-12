# Checkout sesuai referensi Figma

Referensi: `C:/Users/HYPE/Downloads/Checkout1.png` (1280 × 926).
Route `/checkout`, middleware autentikasi, dan proses pembuatan pesanan yang
sudah ada dipertahankan. Tidak ada perubahan skema atau data MySQL db_ppl.

## File implementasi

- `resources/views/pelanggan/checkout.blade.php`: breadcrumb alur Keranjang,
  kartu alamat dengan radio dan tautan tambah/ubah, tiga kelompok pembayaran,
  penyedia aktif dari database, ringkasan bergambar, serta tombol Bayar Sekarang.
  Catatan pesanan tetap tersedia sebagai bagian yang dapat dibuka.
- `public/css/pelanggan.css`: aturan checkout lama diganti; perubahan ringkasan
  dibatasi body `.checkout`. Desktop dua kolom, tablet/mobile satu kolom.
- `public/js/pelanggan.js`: pemilihan alamat mengirim form GET yang sudah ada;
  pilihan penyedia memperbarui ID metode pembayaran yang dikirim ke server.
- `app/Http/Controllers/Pelanggan/CheckoutController.php`: hanya pengambilan data
  tampilan pada `index` diperluas untuk foto/material produk dan rincian wilayah.
  Validasi, perhitungan uang dalam satuan sen, transaksi, penguncian stok,
  kepemilikan alamat, status pembayaran, dan token antipengulangan tidak diubah.
- `tests/Feature/PelangganLanjutanTest.php`: pengujian pengelompokan beberapa
  penyedia bank, pengecualian metode nonaktif, dan QRIS belum siap.

## Hasil pengukuran desktop

| Komponen | Figma, estimasi | Hasil browser |
| --- | --- | --- |
| Posisi awal kartu | y212 | y211,78 |
| Kartu alamat | x24; 813 × 242px | x24; 813 × 242px |
| Kartu pembayaran | x24, y478 | x24, y477,78 |
| Ringkasan | x861; 395 × 392px | x861; 395 × 392,78px |
| Jarak kolom | 24px | 24px |
| Tombol pembayaran | 48px | 48px |

Kartu pembayaran lebih tinggi (402px) dibanding gambar referensi karena fitur
catatan pesanan dipertahankan dan terdapat penjelasan metode yang belum siap.
Tidak dipotong atau diperkecil secara paksa. Ikon Akun memakai aturan bersama
32px yang sama dengan Beranda; navbar dan konten halaman lain tidak didesain ulang.

## Data asli dan integrasi

Pratinjau memakai produk, alamat tersimpan, material, dan tarif ongkir dari MySQL
db_ppl. Nilai saat pemeriksaan: subtotal Rp2.000, ongkir Rp20.000, total Rp22.000.
Nama/alamat yang tampak adalah isi database yang sudah ada, bukan data baru yang
ditambahkan untuk screenshot. Gambar produk belum tersedia: placeholder asli
dipertahankan. Tidak dibuat badge alamat Utama atau lokasi rak karena tidak ada
data yang mendukungnya.

Hanya DANA yang siap digunakan pada data saat pemeriksaan. Transfer Bank tidak
memiliki metode aktif, sedangkan QRIS belum memiliki informasi lengkap. Keduanya
ditampilkan nonaktif, bukan dianggap sudah terintegrasi. Penyedia hanya diberi
label bila tercatat di database. Bila satu jenis memiliki beberapa penyedia siap,
pelanggan dapat memilih penyedia; server tetap menerima ID metode yang sebenarnya.

Bayar Sekarang meneruskan proses pemesanan lama. Pembayaran tetap berstatus
Menunggu dan dilanjutkan dengan instruksi/bukti serta verifikasi yang sudah ada.
Tidak ditambahkan payment gateway atau status berhasil otomatis.

## Pengujian dan batasan

- 26 tes pelanggan lulus, 254 assertions, menggunakan SQLite dalam memori.
  Cakupan mencakup pilihan produk, stok, ongkir, metode nonaktif/tidak lengkap,
  alamat milik akun lain, nilai total server, pengulangan token, dan rollback.
- Blade berhasil dikompilasi.
- Chrome pada lebar 1280, 768, dan 390px; zoom 100%, device scale factor 1.
  Screenshot final dibandingkan dengan referensi. Tidak ada overflow horizontal
  atau footer. Radio pembayaran yang siap dapat dipilih; hanya satu pilihan aktif.
- Untuk browser, checkout dirender melalui controller asli dengan data MySQL dan
  session keranjang sementara dalam memori. Tidak tersedia kredensial login
  pelanggan, sehingga bukan pengujian sesi login live pada route checkout.
  Tidak ada pesanan nyata dibuat. Proses submit, tambah/ubah alamat, kepemilikan,
  dan validasi diuji melalui pengujian aplikasi yang menggunakan database terpisah.
- Screenshot Beranda, Produk, Keranjang, dan dashboard admin sebelum/sesudah
  identik secara byte. Perbedaan screenshot Tentang Kami terbatas pada area foto
  SVG (x65–525, y196–540) karena waktu pemuatan aset; bagian lainnya identik.
  Kode halaman Tentang Kami dan admin tidak diubah.

[Sebelum desktop](../storage/app/referensi/checkout-sebelum-1280.png)

[Sesudah desktop](../storage/app/referensi/checkout-sesudah-1280.png)

[Sesudah mobile](../storage/app/referensi/checkout-sesudah-390.png)

[Pengukuran akhir](../storage/app/referensi/checkout-sesudah.json)
