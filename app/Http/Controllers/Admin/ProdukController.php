<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'harga' => ['required', 'regex:/\A[0-9]{1,13}(?:\.0{1,2})?\z/'],
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

        $grosir = $this->validasiGrosir($request, $validated['harga']);

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
                'grosir_aktif' => $request->boolean('grosir_aktif'),
                'stok' => $validated['stok'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'gambar' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->simpanGrosir($produkId, $grosir);
            $kodeBarang = 'BRG' . str_pad($produkId, 5, '0', STR_PAD_LEFT);

            DB::table('produk')
                ->where('id', $produkId)
                ->update(['kode_barang' => $kodeBarang]);

            // Menyimpan semua gambar ke kolom produk.gambar
            if ($request->hasFile('gambar')) {
                foreach ($request->file('gambar') as $file) {
                    $path = $file->store('produk', 'public');
                    $pathGambar[] = $path;
                }

                DB::table('produk')
                    ->where('id', $produkId)
                    ->update([
                        'gambar' => json_encode($pathGambar),
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

        // Mengambil semua gambar dari kolom JSON produk.gambar
        $daftarGambar = json_decode($produk->gambar ?? '[]', true);

        if (!is_array($daftarGambar)) {
            $daftarGambar = [];
        }

        // Menyesuaikan format agar tetap cocok dengan edit.blade.php
        $gambarProduk = collect($daftarGambar)
            ->values()
            ->map(function ($path, $index) {
                return (object) [
                    'id' => $index,
                    'path_gambar' => $path,
                ];
            });

        $grosir = DB::table('harga_grosir')->where('produk_id', $id)->orderBy('min_qty')->get()->all();

        return view('admin.produk.edit', compact(
            'produk',
            'kategori',
            'material',
            'satuan',
            'gambarProduk',
            'grosir'
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
            'harga' => ['required', 'regex:/\A[0-9]{1,13}(?:\.0{1,2})?\z/'],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_awal' => ['required', 'integer', 'min:0'],
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

        $pathGambarBaru = [];

        try {
            DB::beginTransaction();

            $produk = DB::table('produk')->where('id', $id)->lockForUpdate()->firstOrFail();
            if ((int) $produk->stok !== (int) $validated['stok_awal']) {
                DB::rollBack();

                return redirect()->route('admin.produk.edit', $id)->withErrors([
                    'stok' => 'Stok berubah sejak formulir dibuka. Formulir telah dimuat ulang dengan stok terbaru. Periksa kembali perubahan Anda sebelum menyimpan.',
                ]);
            }

            $ubahGrosir = $request->hasAny(['grosir_aktif', 'grosir']);
            if ($ubahGrosir) {
                $grosir = $this->validasiGrosir($request, $validated['harga']);
            } else {
                $tingkatTersimpan = DB::table('harga_grosir')->where('produk_id', $id)->orderBy('min_qty')->get()
                    ->map(fn ($tier) => (array) $tier)->all();
                $this->validasiUrutanGrosir($tingkatTersimpan, (int) $validated['harga']);
            }

            $gambarLama = json_decode($produk->gambar ?? '[]', true);
            $gambarLama = is_array($gambarLama) ? $gambarLama : [];
            if (count($gambarLama) + count($request->file('gambar', [])) > 4) {
                DB::rollBack();

                return back()->withInput()->withErrors(['gambar' => 'Jumlah keseluruhan gambar maksimal 4.']);
            }

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
                    'grosir_aktif' => $ubahGrosir ? $request->boolean('grosir_aktif') : $produk->grosir_aktif,
                    'stok' => $validated['stok'],
                    'deskripsi' => $validated['deskripsi'] ?? null,
                    'updated_at' => now(),
                ]);

            if ($ubahGrosir) {
                $this->simpanGrosir($id, $grosir);
            }

            // Menambahkan gambar baru tanpa menghapus gambar lama
            if ($request->hasFile('gambar')) {
                foreach ($request->file('gambar') as $file) {
                    $path = $file->store('produk', 'public');
                    $pathGambarBaru[] = $path;
                }

                // Menggabungkan gambar lama dan gambar baru
                $semuaGambar = array_merge($gambarLama, $pathGambarBaru);

                // Menyimpan semua path gambar ke tabel produk
                DB::table('produk')
                    ->where('id', $id)
                    ->update([
                        'gambar' => json_encode(array_values($semuaGambar)),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.produk.index')
                ->with('success', 'Produk berhasil diperbarui.');

        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;
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

    private function validasiGrosir(Request $request, string $hargaNormal): array
    {
        $data = $request->validate([
            'grosir_aktif' => ['sometimes', 'boolean'],
            'grosir' => [Rule::requiredIf($request->boolean('grosir_aktif')), 'array'],
            'grosir.*' => ['required', 'array:min_qty,harga_satuan'],
            'grosir.*.min_qty' => ['required', 'integer', 'min:2', 'max:4294967295', 'distinct'],
            'grosir.*.harga_satuan' => ['required', 'integer', 'min:1', 'max:9999999999999'],
        ]);

        $grosir = collect($data['grosir'] ?? [])->sortBy('min_qty')->values()->all();
        $this->validasiUrutanGrosir($grosir, (int) $hargaNormal);

        return $grosir;
    }

    private function validasiUrutanGrosir(array $grosir, int $hargaNormal): void
    {
        $hargaSebelumnya = $hargaNormal;
        $minimalSebelumnya = 1;
        foreach ($grosir as $tier) {
            if ($tier['min_qty'] <= $minimalSebelumnya || $tier['harga_satuan'] < 1 || $tier['harga_satuan'] >= $hargaSebelumnya) {
                throw ValidationException::withMessages([
                    'grosir' => 'Minimal pembelian harus unik dan harga grosir harus semakin murah dibandingkan harga normal atau tingkatan sebelumnya.',
                ]);
            }

            $minimalSebelumnya = (int) $tier['min_qty'];
            $hargaSebelumnya = (int) $tier['harga_satuan'];
        }
    }

    private function simpanGrosir(int $produkId, array $grosir): void
    {
        DB::table('harga_grosir')->where('produk_id', $produkId)->delete();
        foreach ($grosir as $index => $tier) {
            $minimalBerikutnya = $grosir[$index + 1]['min_qty'] ?? null;
            DB::table('harga_grosir')->insert([
                'produk_id' => $produkId,
                'min_qty' => $tier['min_qty'],
                'max_qty' => $minimalBerikutnya === null ? null : $minimalBerikutnya - 1,
                'harga_satuan' => $tier['harga_satuan'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function hapusGambar($id)
    {
        // ID gambar lama sekarang digunakan sebagai indeks array JSON.
        // ID produk dikirim melalui parameter query.
        $produkId = request()->query('produk_id');

        $produk = DB::table('produk')->where('id', $produkId)->first();

        if (!$produk) {
            return redirect()
                ->route('admin.produk.index')
                ->with('error', 'Produk tidak ditemukan.');
        }

        $daftarGambar = json_decode($produk->gambar ?? '[]', true);

        if (!is_array($daftarGambar)) {
            $daftarGambar = [];
        }

        // Memastikan indeks gambar valid
        if (
            !ctype_digit((string) $id) ||
            !array_key_exists((int) $id, $daftarGambar)
        ) {
            return redirect()
                ->route('admin.produk.edit', $produk->id)
                ->with('error', 'Gambar tidak ditemukan.');
        }

        $index = (int) $id;
        $pathGambar = $daftarGambar[$index];

        // Menghapus satu gambar dari array
        array_splice($daftarGambar, $index, 1);

        try {
            DB::table('produk')
                ->where('id', $produk->id)
                ->update([
                    'gambar' => json_encode(array_values($daftarGambar)),
                    'updated_at' => now(),
                ]);

            // Menghapus file fisik setelah database berhasil diperbarui
            if (
                is_string($pathGambar) &&
                str_starts_with($pathGambar, 'produk/')
            ) {
                Storage::disk('public')->delete($pathGambar);
            }

            return redirect()
                ->route('admin.produk.edit', $produk->id)
                ->with('success', 'Gambar produk berhasil dihapus.');

        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.produk.edit', $produk->id)
                ->with('error', 'Gagal menghapus gambar produk.');
        }
    }

    public function destroy($id)
    {
        $produk = DB::table('produk')->where('id', $id)->first();

        if (!$produk) {
            return redirect()
                ->route('admin.produk.index')
                ->with('error', 'Produk tidak ditemukan.');
        }

        // Mengambil daftar gambar dari kolom JSON
        $daftarGambar = json_decode($produk->gambar ?? '[]', true);

        if (!is_array($daftarGambar)) {
            $daftarGambar = [];
        }

        try {
            // Hapus data produk dari database terlebih dahulu
            DB::table('produk')
                ->where('id', $id)
                ->delete();

            // Hapus semua file gambar setelah data produk berhasil dihapus
            foreach ($daftarGambar as $path) {
                if (
                    is_string($path) &&
                    str_starts_with($path, 'produk/')
                ) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()
                ->route('admin.produk.index')
                ->with('success', 'Produk berhasil dihapus.');

        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.produk.index')
                ->with('error', 'Produk gagal dihapus.');
        }
    }
}
