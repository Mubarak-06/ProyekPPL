<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DataMasterController extends Controller
{
    public function index()
    {
        $kategori = DB::table('kategori')
            ->orderBy('id', 'asc')
            ->get();

        $material = DB::table('material')
            ->orderBy('id', 'asc')
            ->get();

        $satuan = DB::table('satuan')
            ->orderBy('id', 'asc')
            ->get();

        $kategoriMaterial = DB::table('kategori_material')
            ->get()
            ->groupBy('kategori_id');

        $kategoriSatuan = DB::table('kategori_satuan')
            ->get()
            ->groupBy('kategori_id');

        return view('admin.data-master.index', compact(
            'kategori',
            'material',
            'satuan',
            'kategoriMaterial',
            'kategoriSatuan'
        ));
    }
    
    public function storeKategori(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama_kategori'),
            ],
            'material_id' => ['nullable', 'array'],
            'material_id.*' => [
                'integer',
                'distinct',
                Rule::exists('material', 'id'),
            ],
            'satuan_id' => ['required', 'array', 'min:1'],
            'satuan_id.*' => [
                'integer',
                'distinct',
                Rule::exists('satuan', 'id'),
            ],
        ]);

        DB::transaction(function () use ($validated) {

            $kategoriId = DB::table('kategori')->insertGetId([
                'nama_kategori' => $validated['nama_kategori'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($validated['material_id'] ?? [] as $materialId) {
                DB::table('kategori_material')->insert([
                    'kategori_id' => $kategoriId,
                    'material_id' => $materialId,
                ]);
            }

            foreach ($validated['satuan_id'] as $satuanId) {
                DB::table('kategori_satuan')->insert([
                    'kategori_id' => $kategoriId,
                    'satuan_id' => $satuanId,
                ]);
            }
        });

        return redirect()
            ->route('admin.data-master.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }
    
    public function updateKategori(Request $request, $id)
    {
        $kategori = DB::table('kategori')->where('id', $id)->first();

        if (!$kategori) {
            abort(404);
        }

        $validated = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama_kategori')->ignore($id),
            ],
            'material_id' => ['nullable', 'array'],
            'material_id.*' => [
                'integer',
                'distinct',
                Rule::exists('material', 'id'),
            ],
            'satuan_id' => ['required', 'array', 'min:1'],
            'satuan_id.*' => [
                'integer',
                'distinct',
                Rule::exists('satuan', 'id'),
            ],
        ]);
        
        // Periksa material yang masih digunakan produk
        $materialDigunakan = DB::table('produk')
            ->where('kategori_id', $id)
            ->whereNotNull('material_id')
            ->distinct()
            ->pluck('material_id')
            ->map(fn ($nilai) => (int) $nilai)
            ->toArray();

        $materialDipilih = array_map(
            'intval',
            $validated['material_id'] ?? []
        );

        $materialDilepas = array_diff(
            $materialDigunakan,
            $materialDipilih
        );

        if (!empty($materialDilepas)) {
            return back()->withErrors([
                'material_id' =>
                    'Material tidak dapat dilepas karena masih digunakan oleh produk.'
            ]);
        }

        // Periksa satuan yang masih digunakan produk
        $satuanDigunakan = DB::table('produk')
            ->where('kategori_id', $id)
            ->distinct()
            ->pluck('satuan_id')
            ->map(fn ($nilai) => (int) $nilai)
            ->toArray();

        $satuanDipilih = array_map(
            'intval',
            $validated['satuan_id']
        );

        $satuanDilepas = array_diff(
            $satuanDigunakan,
            $satuanDipilih
        );

        if (!empty($satuanDilepas)) {
            return back()->withErrors([
                'satuan_id' =>
                    'Satuan tidak dapat dilepas karena masih digunakan oleh produk.'
            ]);
        }

        DB::transaction(function () use ($id, $validated) {

            DB::table('kategori')
                ->where('id', $id)
                ->update([
                    'nama_kategori' => $validated['nama_kategori'],
                    'updated_at' => now(),
                ]);

            DB::table('kategori_material')
                ->where('kategori_id', $id)
                ->delete();

            foreach ($validated['material_id'] ?? [] as $materialId) {
                DB::table('kategori_material')->insert([
                    'kategori_id' => $id,
                    'material_id' => $materialId,
                ]);
            }

            DB::table('kategori_satuan')
                ->where('kategori_id', $id)
                ->delete();

            foreach ($validated['satuan_id'] as $satuanId) {
                DB::table('kategori_satuan')->insert([
                    'kategori_id' => $id,
                    'satuan_id' => $satuanId,
                ]);
            }
        });

        return redirect()
            ->route('admin.data-master.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }
    
    public function destroyKategori($id)
    {
        $kategori = DB::table('kategori')
            ->where('id', $id)
            ->first();

        if (!$kategori) {
            abort(404);
        }

        // Periksa apakah kategori masih digunakan produk
        $jumlahProduk = DB::table('produk')
            ->where('kategori_id', $id)
            ->count();

        if ($jumlahProduk > 0) {
            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_kategori' =>
                        'Kategori tidak dapat dihapus karena masih digunakan oleh '
                        . $jumlahProduk . ' produk.'
                ]);
        }

        // Hapus hubungan dan kategori dalam satu transaksi
        try {
            DB::transaction(function () use ($id) {

                DB::table('kategori_material')
                    ->where('kategori_id', $id)
                    ->delete();

                DB::table('kategori_satuan')
                    ->where('kategori_id', $id)
                    ->delete();

                DB::table('kategori')
                    ->where('id', $id)
                    ->delete();
            });

            return redirect()
                ->route('admin.data-master.index')
                ->with('success', 'Kategori berhasil dihapus.');

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_kategori' =>
                        'Kategori gagal dihapus. Silakan coba kembali.'
                ]);
        }
    }
    
    public function storeMaterial(Request $request)
    {
        $validated = $request->validate([
            'nama_material' => [
                'required',
                'string',
                'max:100',
                Rule::unique('material', 'nama_material'),
            ],
        ], [
            'nama_material.required' => 'Nama material wajib diisi.',
            'nama_material.max' => 'Nama material maksimal 100 karakter.',
            'nama_material.unique' => 'Nama material sudah tersedia.',
        ]);

        DB::table('material')->insert([
            'nama_material' => $validated['nama_material'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.data-master.index')
            ->with('success', 'Material berhasil ditambahkan.');
    }
    
    public function destroyMaterial($id)
    {
        $material = DB::table('material')
            ->where('id', $id)
            ->first();

        if (!$material) {
            abort(404);
        }

        try {
            DB::transaction(function () use ($id) {

                // Kunci data material selama proses penghapusan
                DB::table('material')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                // Periksa apakah material masih digunakan produk
                $jumlahProduk = DB::table('produk')
                    ->where('material_id', $id)
                    ->count();

                if ($jumlahProduk > 0) {
                    throw new \DomainException(
                        'Material tidak dapat dihapus karena masih digunakan oleh '
                        . $jumlahProduk . ' produk.'
                    );
                }

                // Hapus hubungan material dengan kategori
                DB::table('kategori_material')
                    ->where('material_id', $id)
                    ->delete();

                // Hapus data material
                DB::table('material')
                    ->where('id', $id)
                    ->delete();
            });

            return redirect()
                ->route('admin.data-master.index')
                ->with('success', 'Material berhasil dihapus.');

        } catch (\DomainException $e) {
            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_material' => $e->getMessage()
                ]);

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_material' =>
                        'Material gagal dihapus. Silakan coba kembali.'
                ]);
        }
    }
    
    public function storeSatuan(Request $request)
    {
        $validated = $request->validate([
            'nama_satuan' => [
                'required',
                'string',
                'max:100',
                Rule::unique('satuan', 'nama_satuan'),
            ],
        ], [
            'nama_satuan.required' => 'Nama satuan wajib diisi.',
            'nama_satuan.max' => 'Nama satuan maksimal 100 karakter.',
            'nama_satuan.unique' => 'Nama satuan sudah tersedia.',
        ]);

        DB::table('satuan')->insert([
            'nama_satuan' => $validated['nama_satuan'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.data-master.index')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }
    
    public function destroySatuan($id)
    {
        $satuan = DB::table('satuan')
            ->where('id', $id)
            ->first();

        if (!$satuan) {
            abort(404);
        }

        try {
            DB::transaction(function () use ($id) {

                // Kunci data satuan selama proses penghapusan
                $satuan = DB::table('satuan')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$satuan) {
                    abort(404);
                }

                // Periksa apakah satuan masih digunakan produk
                $jumlahProduk = DB::table('produk')
                    ->where('satuan_id', $id)
                    ->count();

                if ($jumlahProduk > 0) {
                    throw new \DomainException(
                        'Satuan tidak dapat dihapus karena masih digunakan oleh '
                        . $jumlahProduk . ' produk.'
                    );
                }

                // Hapus hubungan satuan dengan kategori
                DB::table('kategori_satuan')
                    ->where('satuan_id', $id)
                    ->delete();

                // Hapus data satuan
                DB::table('satuan')
                    ->where('id', $id)
                    ->delete();
            });

            return redirect()
                ->route('admin.data-master.index')
                ->with('success', 'Satuan berhasil dihapus.');

        } catch (\DomainException $e) {
            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_satuan' => $e->getMessage()
                ]);

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.data-master.index')
                ->withErrors([
                    'hapus_satuan' =>
                        'Satuan gagal dihapus. Silakan coba kembali.'
                ]);
        }
    }
}