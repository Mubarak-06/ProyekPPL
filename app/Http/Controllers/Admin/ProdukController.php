<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProdukController extends Controller
{
    public function index()
    {
        /*
        gsn meambil data dari tabel produk di databse
        meambil kategori setiap produkny
        meambil material jika tersedia
        meambil satuan setiap produkny
        select gsn menentukan kolom yg diambil
        orderbydesc gsn meurut akan produk yg hnyr dulu
        get gsn menjalan akan query
        return menampil akan datany ke halaman produk
        */
        $produk = DB::table('produk')
            ->join('kategori', 'produk.kategori_id', '=', 'kategori.id')
            ->leftJoin('material', 'produk.material_id', '=', 'material.id')
            ->join('satuan', 'produk.satuan_id', '=', 'satuan.id')
            ->select(
                'produk.*',
                'kategori.nama_kategori',
                'material.nama_material',
                'satuan.nama_satuan'
            )
            ->orderByDesc('produk.id')
            ->get();

        return view('admin.produk.index', compact('produk'));
    }
    
    public function create()
    {
        $kategori = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->get();

        return view('admin.produk.create', compact('kategori'));
    }
    
    public function getDropdownData($kategoriId)
    {
        $material = DB::table('material')
            ->join('kategori_material', 'material.id', '=', 'kategori_material.material_id')
            ->where('kategori_material.kategori_id', $kategoriId)
            ->select('material.id', 'material.nama_material')
            ->orderBy('material.nama_material')
            ->get();

        $satuan = DB::table('satuan')
            ->join('kategori_satuan', 'satuan.id', '=', 'kategori_satuan.satuan_id')
            ->where('kategori_satuan.kategori_id', $kategoriId)
            ->select('satuan.id', 'satuan.nama_satuan')
            ->orderBy('satuan.nama_satuan')
            ->get();

        return response()->json([
            'material' => $material,
            'satuan' => $satuan
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi data produk
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'exists:kategori,id'],
            'harga' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'satuan_id' => [
                'required',
                Rule::exists('kategori_satuan', 'satuan_id')
                    ->where('kategori_id', $request->kategori_id)
            ],
            'berat' => ['required', 'numeric', 'gt:0'],
            'ukuran' => ['nullable', 'string', 'max:100'],
            'material_id' => ['nullable', 'exists:material,id'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => ['nullable', 'array', 'max:4'],
            'gambar.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // 2. Memeriksa kategori produk
        $kategori = DB::table('kategori')
            ->where('id', $validated['kategori_id'])
            ->first();

        $menggunakanMaterial = in_array(
            strtolower(trim($kategori->nama_kategori)),
            ['baut', 'mur', 'ring', 'sekrup']
        );

        if ($menggunakanMaterial) {
            $request->validate([
                'ukuran' => ['required', 'string', 'max:100'],
                'material_id' => [
                    'required',
                    Rule::exists('kategori_material', 'material_id')
                        ->where('kategori_id', $validated['kategori_id'])
                ],
            ]);
        } else {
            $validated['ukuran'] = null;
            $validated['material_id'] = null;
        }

        // 3. Menyimpan produk dan gambar
        $pathGambar = [];

        try {
            DB::beginTransaction();

            // Membuat kode barang otomatis berdasarkan ID produk
            $produkId = DB::table('produk')->insertGetId([
                'kode_barang' => 'TMP-' . bin2hex(random_bytes(12)),
                'nama_produk' => $validated['nama_produk'],
                'kategori_id' => $validated['kategori_id'],
                'material_id' => $validated['material_id'] ?? null,
                'satuan_id' => $validated['satuan_id'],
                'ukuran' => $validated['ukuran'] ?? null,
                'berat' => $validated['berat'],
                'harga' => $validated['harga'],
                'stok' => $validated['stok'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'gambar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $kodeBarang = 'BRG' . str_pad($produkId, 5, '0', STR_PAD_LEFT);

            DB::table('produk')
                ->where('id', $produkId)
                ->update(['kode_barang' => $kodeBarang]);

            // Menyimpan gambar produk
            if ($request->hasFile('gambar')) {
                foreach ($request->file('gambar') as $index => $file) {
                    $path = $file->store('produk', 'public');
                    $pathGambar[] = $path;

                    DB::table('gambar_produk')->insert([
                        'produk_id' => $produkId,
                        'path_gambar' => $path,
                        'urutan' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Gambar pertama menjadi gambar utama
                DB::table('produk')
                    ->where('id', $produkId)
                    ->update([
                        'gambar' => $pathGambar[0],
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.produk.index')
                ->with('success', 'Produk berhasil ditambahkan.');

        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($pathGambar as $path) {
                Storage::disk('public')->delete($path);
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'simpan' => 'Produk gagal disimpan. Silakan coba kembali.'
                ]);
        }
    }
    public function edit($id)
    {
        // Mengambil data produk berdasarkan ID
        $produk = DB::table('produk')
            ->where('id', $id)
            ->first();

        // Jika produk tidak ditemukan, tampilkan halaman 404
        if (!$produk) {
            abort(404);
        }

        // Mengambil daftar kategori
        $kategori = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->get();

        // Mengambil material sesuai kategori produk
        $material = DB::table('material')
            ->join(
                'kategori_material',
                'material.id',
                '=',
                'kategori_material.material_id'
            )
            ->where('kategori_material.kategori_id', $produk->kategori_id)
            ->select('material.id', 'material.nama_material')
            ->orderBy('material.nama_material')
            ->get();

        // Mengambil satuan sesuai kategori produk
        $satuan = DB::table('satuan')
            ->join(
                'kategori_satuan',
                'satuan.id',
                '=',
                'kategori_satuan.satuan_id'
            )
            ->where('kategori_satuan.kategori_id', $produk->kategori_id)
            ->select('satuan.id', 'satuan.nama_satuan')
            ->orderBy('satuan.nama_satuan')
            ->get();

        // Mengambil semua gambar produk
        $gambarProduk = DB::table('gambar_produk')
            ->where('produk_id', $produk->id)
            ->orderBy('urutan')
            ->get();

        return view('admin.produk.edit', compact(
            'produk',
            'kategori',
            'material',
            'satuan',
            'gambarProduk'
        ));
    }

    public function update(Request $request, $id)
    {
        // Memastikan produk tersedia
        $produk = DB::table('produk')->where('id', $id)->first();

        if (!$produk) {
            abort(404);
        }

        // Validasi data produk
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:150'],
            'kategori_id' => ['required', 'exists:kategori,id'],
            'harga' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'satuan_id' => [
                'required',
                Rule::exists('kategori_satuan', 'satuan_id')
                    ->where('kategori_id', $request->kategori_id)
            ],
            'berat' => ['required', 'numeric', 'gt:0'],
            'ukuran' => ['nullable', 'string', 'max:100'],
            'material_id' => ['nullable', 'exists:material,id'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => ['nullable', 'array', 'max:4'],
            'gambar.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // Memeriksa kategori produk
        $kategori = DB::table('kategori')
            ->where('id', $validated['kategori_id'])
            ->first();

        $menggunakanMaterial = in_array(
            strtolower(trim($kategori->nama_kategori)),
            ['baut', 'mur', 'ring', 'sekrup']
        );

        if ($menggunakanMaterial) {
            $request->validate([
                'ukuran' => ['required', 'string', 'max:100'],
                'material_id' => [
                    'required',
                    Rule::exists('kategori_material', 'material_id')
                        ->where('kategori_id', $validated['kategori_id'])
                ],
            ]);
        } else {
            $validated['ukuran'] = null;
            $validated['material_id'] = null;
        }

        // Membatasi jumlah gambar keseluruhan menjadi 4
        $jumlahGambarLama = DB::table('gambar_produk')
            ->where('produk_id', $id)
            ->count();

        $jumlahGambarBaru = count($request->file('gambar', []));

        if ($jumlahGambarLama + $jumlahGambarBaru > 4) {
            return back()
                ->withInput()
                ->withErrors([
                    'gambar' => 'Jumlah keseluruhan gambar maksimal 4.'
                ]);
        }

        $pathGambarBaru = [];

        try {
            DB::beginTransaction();

            // Memperbarui data produk
            DB::table('produk')
                ->where('id', $id)
                ->update([
                    'nama_produk' => $validated['nama_produk'],
                    'kategori_id' => $validated['kategori_id'],
                    'material_id' => $validated['material_id'] ?? null,
                    'satuan_id' => $validated['satuan_id'],
                    'ukuran' => $validated['ukuran'] ?? null,
                    'berat' => $validated['berat'],
                    'harga' => $validated['harga'],
                    'stok' => $validated['stok'],
                    'deskripsi' => $validated['deskripsi'] ?? null,
                    'updated_at' => now(),
                ]);

            // Menambahkan gambar baru tanpa menghapus gambar lama
            if ($request->hasFile('gambar')) {
                $urutanTerakhir = DB::table('gambar_produk')
                    ->where('produk_id', $id)
                    ->max('urutan') ?? 0;

                foreach ($request->file('gambar') as $file) {
                    $path = $file->store('produk', 'public');
                    $pathGambarBaru[] = $path;
                    $urutanTerakhir++;

                    DB::table('gambar_produk')->insert([
                        'produk_id' => $id,
                        'path_gambar' => $path,
                        'urutan' => $urutanTerakhir,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Menetapkan gambar utama jika belum ada
            if (!$produk->gambar) {
                $gambarUtama = DB::table('gambar_produk')
                    ->where('produk_id', $id)
                    ->orderBy('urutan')
                    ->value('path_gambar');

                DB::table('produk')
                    ->where('id', $id)
                    ->update(['gambar' => $gambarUtama]);
            }

            DB::commit();

            return redirect()
                ->route('admin.produk.index')
                ->with('success', 'Produk berhasil diperbarui.');

        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($pathGambarBaru as $path) {
                Storage::disk('public')->delete($path);
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'update' => 'Produk gagal diperbarui. Silakan coba kembali.'
                ]);
        }
    }

    public function hapusGambar($id)
    {
        // Mencari gambar berdasarkan ID
        $gambar = DB::table('gambar_produk')
            ->where('id', $id)
            ->first();

        if (!$gambar) {
            abort(404);
        }

        // Memastikan produk masih tersedia
        $produk = DB::table('produk')
            ->where('id', $gambar->produk_id)
            ->first();

        if (!$produk) {
            abort(404);
        }

        try {
            DB::transaction(function () use ($gambar, $produk) {

                // Menghapus data gambar dari database
                DB::table('gambar_produk')
                    ->where('id', $gambar->id)
                    ->delete();

                // Jika gambar yang dihapus merupakan gambar utama
                if ($produk->gambar === $gambar->path_gambar) {

                    // Mengambil gambar pengganti
                    $gambarPengganti = DB::table('gambar_produk')
                        ->where('produk_id', $produk->id)
                        ->orderBy('urutan')
                        ->orderBy('id')
                        ->value('path_gambar');

                    // Memperbarui gambar utama
                    DB::table('produk')
                        ->where('id', $produk->id)
                        ->update([
                            'gambar' => $gambarPengganti,
                            'updated_at' => now()
                        ]);
                }
            });

            // Menghapus file setelah perubahan database berhasil
            Storage::disk('public')->delete($gambar->path_gambar);

            return redirect()
                ->route('admin.produk.edit', $produk->id)
                ->with('success', 'Gambar produk berhasil dihapus.');

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.produk.edit', $produk->id)
                ->withErrors([
                    'gambar' => 'Gambar gagal dihapus. Silakan coba kembali.'
                ]);
        }
    }
    
    public function destroy($id)
    {
        // Memastikan produk tersedia
        $produk = DB::table('produk')
            ->where('id', $id)
            ->first();

        if (!$produk) {
            abort(404);
        }

        // Mengambil seluruh gambar yang terkait dengan produk
        $gambarProduk = DB::table('gambar_produk')
            ->where('produk_id', $id)
            ->pluck('path_gambar')
            ->toArray();

        // Memastikan gambar utama juga tercatat
        if ($produk->gambar && !in_array($produk->gambar, $gambarProduk)) {
            $gambarProduk[] = $produk->gambar;
        }

        try {
            // Menghapus produk dan relasi gambarnya dari database
            DB::transaction(function () use ($id) {

                DB::table('gambar_produk')
                    ->where('produk_id', $id)
                    ->delete();

                DB::table('produk')
                    ->where('id', $id)
                    ->delete();
            });

            // Menghapus file gambar setelah transaksi berhasil
            foreach ($gambarProduk as $path) {
                Storage::disk('public')->delete($path);
            }

            return redirect()
                ->route('admin.produk.index')
                ->with('success', 'Produk berhasil dihapus.');

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.produk.index')
                ->withErrors([
                    'hapus' => 'Produk gagal dihapus. Silakan coba kembali.'
                ]);
        }
    }
}
