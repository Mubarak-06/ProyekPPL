# Keranjang Belanja sesuai referensi

Referensi: `C:/Users/HYPE/Downloads/Keranjang.png`, dikonfirmasi pengguna.
Ukuran gambar 1280 × 488. Implementasi mempertahankan route, controller,
database produk, dan state keranjang berbasis session yang sudah ada.

## File produksi yang diubah

- `resources/views/pelanggan/keranjang.blade.php`: header pilihan dipindahkan
  ke kartu daftar produk; baris produk dipisah menjadi checkbox, foto, informasi,
  kontrol jumlah, subtotal, dan tombol hapus. Ringkasan mengikuti referensi dan
  menghitung jenis produk yang dipilih. Empty state tetap tersedia.
- `public/css/pelanggan.css`: selector keranjang lama dibersihkan dan tata letak
  disesuaikan. Style ringkasan dan navbar baru dibatasi pada `.keranjang`, sehingga
  checkout dan halaman lain tidak ikut berubah. Desktop dua kolom, tablet/ponsel
  satu kolom. Tidak ditambahkan framework atau footer.
- `public/js/pelanggan.js`: perubahan jumlah yang diketik langsung otomatis
  disimpan melalui form yang sudah ada, setelah validasi. Checkbox Pilih Semua
  mendukung keadaan sebagian produk dipilih. Plus/minus dan pemilihan memakai
  submit form lama; subtotal dan total dihitung ulang oleh server. Tombol simpan
  manual tetap disediakan melalui noscript jika JavaScript tidak aktif.
- `resources/views/pelanggan/layouts/app.blade.php`: ikon akun dan panah disesuaikan
  hanya untuk route keranjang. Fungsi tautan/navbar lainnya tetap sama.

## Perbandingan desktop pada 1280px

| Komponen | Target referensi | Hasil browser |
| --- | --- | --- |
| Kartu produk | x32, y149; 803 × 183px | x32, y149; 803 × 183px |
| Header Pilih Semua | tinggi 60px | 60px |
| Kartu ringkasan | x859, y149; 389 × 315px | x859, y149; 389 × 314,58px |
| Jarak antarkolom | 24px | 24px |
| Tombol checkout | tinggi 48px | 48px |

Screenshot final diperiksa terhadap referensi. Nama/harga/foto mengikuti produk
asli: Baut Hexagonal M8, Rp2.000, stok 150 Pcs pada waktu pemeriksaan. Tidak dibuat
produk Sekrup atau harga Rp250 untuk meniru contoh. Foto produk asli belum tersedia,
sehingga placeholder yang sudah ada dipertahankan. Badge tetap memakai jumlah
unit sesuai logika session sebelumnya. Tidak ada perubahan data produk db_ppl.

## Pengujian

Chrome, zoom 100%, device scale factor 1, lebar 1280, 768, dan 390px. Tidak ada
overflow horizontal. Pada desktop kartu sejajar; pada tablet dan ponsel ringkasan
berada di bawah daftar. Tidak ada footer.

Interaksi diuji pada session browser terpisah memakai produk asli: tambah/kurangi,
subtotal dan total sesuai harga database, Pilih Semua, batal pilihan (total nol dan
checkout nonaktif), pilih produk, input jumlah manual, batas minimum 1 dan maksimum
stok, hapus terpilih dengan konfirmasi, hapus satu produk, serta keadaan kosong.
Checkout mempertahankan route yang sudah ada. Tidak dibuat pesanan nyata.

25 tes pelanggan lulus (243 assertions), memakai SQLite dalam memori, termasuk
pemilihan untuk checkout, validasi stok, dan akses halaman admin. Blade berhasil
dikompilasi. Screenshot Beranda, Semua Produk, Tentang Kami, dan dashboard admin
sebelum/sesudah identik secara byte pada viewport 1280 × 720.

[Screenshot sebelum desktop](../storage/app/referensi/keranjang-live-sebelum-1280.png)

[Screenshot sesudah desktop](../storage/app/referensi/keranjang-live-sesudah-1280.png)

[Screenshot sesudah mobile](../storage/app/referensi/keranjang-live-sesudah-390.png)

[Empty state](../storage/app/referensi/keranjang-kosong-sesudah.png)

[Hasil pengukuran dan interaksi](../storage/app/referensi/keranjang-live-sesudah.json)
