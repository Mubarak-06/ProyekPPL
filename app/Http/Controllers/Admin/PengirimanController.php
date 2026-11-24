<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengiriman;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengirimanController extends Controller
{
    public function index()
    {
        $pengiriman = Pengiriman::with('pesanan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.pengiriman.index', compact('pengiriman'));
    }
    public function show($id)
    {
        $pengiriman = \App\Models\Pengiriman::with([
            'pesanan.user',
            'pesanan.alamat',
            'pesanan.detailPesanan.produk.material',
        ])->findOrFail($id);

        return view('admin.pengiriman.show', compact('pengiriman'));
    }
    public function dataMaster()
    {
        $provinsi = DB::table('provinsi')
            ->orderBy('nama_provinsi')
            ->get();

        $kota = DB::table('kota')
            ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
            ->select('kota.*', 'provinsi.nama_provinsi')
            ->orderBy('kota.nama_kota')
            ->get();

        $kecamatan = DB::table('kecamatan')
            ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
            ->select('kecamatan.*', 'kota.nama_kota')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        $kelurahan = DB::table('kelurahan')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->select('kelurahan.*', 'kecamatan.nama_kecamatan')
            ->orderBy('kelurahan.nama_kelurahan')
            ->get();

        $ongkir = DB::table('ongkir')
            ->join('kelurahan', 'ongkir.kelurahan_id', '=', 'kelurahan.id')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->select(
                'ongkir.*',
                'kelurahan.nama_kelurahan',
                'kecamatan.nama_kecamatan'
            )
            ->orderBy('kelurahan.nama_kelurahan')
            ->get();

        return view('admin.pengiriman.data-master', compact(
            'provinsi',
            'kota',
            'kecamatan',
            'kelurahan',
            'ongkir'
        ));
    }
    public function storeProvinsi(Request $request)
    {
        $validated = $request->validate([
            'nama_provinsi' => [
                'required',
                'string',
                'max:100',
                'unique:provinsi,nama_provinsi',
            ],
        ]);

        DB::table('provinsi')->insert([
            'nama_provinsi' => $validated['nama_provinsi'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data provinsi berhasil ditambahkan.');
    }

    public function updateProvinsi(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_provinsi' => [
                'required',
                'string',
                'max:100',
                Rule::unique('provinsi', 'nama_provinsi')->ignore($id),
            ],
        ]);

        $provinsi = DB::table('provinsi')
            ->where('id', $id)
            ->first();

        if (!$provinsi) {
            abort(404);
        }

        DB::table('provinsi')
            ->where('id', $id)
            ->update([
                'nama_provinsi' => $validated['nama_provinsi'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data provinsi berhasil diperbarui.');
    }

    public function destroyProvinsi($id)
    {
        return DB::transaction(function () use ($id) {
            $provinsi = DB::table('provinsi')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$provinsi) {
                abort(404);
            }

            $digunakan = DB::table('kota')
                ->where('provinsi_id', $id)
                ->exists();

            if ($digunakan) {
                return redirect()
                    ->route('admin.pengiriman.dataMaster')
                    ->with(
                        'error',
                        'Provinsi tidak dapat dihapus karena masih digunakan oleh data kota.'
                    );
            }

            DB::table('provinsi')
                ->where('id', $id)
                ->delete();

            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with('success', 'Data provinsi berhasil dihapus.');
        });
    }

    public function storeKota(Request $request)
    {
        $validated = $request->validate([
            'provinsi_id' => [
                'required',
                'integer',
                'exists:provinsi,id',
            ],
            'nama_kota' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $sudahAda = DB::table('kota')
            ->where('provinsi_id', $validated['provinsi_id'])
            ->where('nama_kota', $validated['nama_kota'])
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with('error', 'Kota tersebut sudah terdaftar pada provinsi yang dipilih.');
        }

        DB::table('kota')->insert([
            'provinsi_id' => $validated['provinsi_id'],
            'nama_kota' => $validated['nama_kota'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data kota berhasil ditambahkan.');
    }

    public function updateKota(Request $request, $id)
    {
        $validated = $request->validate([
            'provinsi_id' => [
                'required',
                'integer',
                'exists:provinsi,id',
            ],
            'nama_kota' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $kota = DB::table('kota')
            ->where('id', $id)
            ->first();

        if (!$kota) {
            abort(404);
        }

        $sudahAda = DB::table('kota')
            ->where('provinsi_id', $validated['provinsi_id'])
            ->where('nama_kota', $validated['nama_kota'])
            ->where('id', '!=', $id)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with('error', 'Kota tersebut sudah terdaftar pada provinsi yang dipilih.');
        }

        DB::table('kota')
            ->where('id', $id)
            ->update([
                'provinsi_id' => $validated['provinsi_id'],
                'nama_kota' => $validated['nama_kota'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data kota berhasil diperbarui.');
    }

    public function destroyKota($id)
    {
        return DB::transaction(function () use ($id) {

            $kota = DB::table('kota')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$kota) {
                abort(404);
            }

            // Periksa apakah kota masih memiliki kecamatan
            $digunakan = DB::table('kecamatan')
                ->where('kota_id', $id)
                ->exists();

            if ($digunakan) {
                return redirect()
                    ->route('admin.pengiriman.dataMaster')
                    ->with(
                        'error',
                        'Kota tidak dapat dihapus karena masih digunakan oleh data kecamatan.'
                    );
            }

            // Hapus kota jika tidak memiliki kecamatan
            DB::table('kota')
                ->where('id', $id)
                ->delete();

            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with('success', 'Data kota berhasil dihapus.');
        });
    }

    public function storeKecamatan(Request $request)
    {
        $validated = $request->validate([
            'kota_id' => [
                'required',
                'integer',
                'exists:kota,id',
            ],
            'nama_kecamatan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $namaKecamatan = trim($validated['nama_kecamatan']);

        $sudahAda = DB::table('kecamatan')
            ->where('kota_id', $validated['kota_id'])
            ->where('nama_kecamatan', $namaKecamatan)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kecamatan tersebut sudah terdaftar pada kota yang dipilih.'
                );
        }

        DB::table('kecamatan')->insert([
            'kota_id' => $validated['kota_id'],
            'nama_kecamatan' => $namaKecamatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data kecamatan berhasil ditambahkan.');
    }

    public function updateKecamatan(Request $request, $id)
    {
        $kecamatan = DB::table('kecamatan')
            ->where('id', $id)
            ->first();

        if (!$kecamatan) {
            abort(404);
        }

        $validated = $request->validate([
            'kota_id' => [
                'required',
                'integer',
                'exists:kota,id',
            ],
            'nama_kecamatan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $namaKecamatan = trim($validated['nama_kecamatan']);

        // Periksa apakah kecamatan sudah ada pada kota yang dipilih
        $sudahAda = DB::table('kecamatan')
            ->where('kota_id', $validated['kota_id'])
            ->where('nama_kecamatan', $namaKecamatan)
            ->where('id', '!=', $id)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kecamatan tersebut sudah terdaftar pada kota yang dipilih.'
                );
        }

        // Perbarui data kecamatan
        DB::table('kecamatan')
            ->where('id', $id)
            ->update([
                'kota_id' => $validated['kota_id'],
                'nama_kecamatan' => $namaKecamatan,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with('success', 'Data kecamatan berhasil diperbarui.');
    }

    public function destroyKecamatan($id)
    {
        return DB::transaction(function () use ($id) {

            // Cari dan kunci data kecamatan
            $kecamatan = DB::table('kecamatan')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$kecamatan) {
                abort(404);
            }

            // Periksa apakah kecamatan masih memiliki kelurahan
            $digunakan = DB::table('kelurahan')
                ->where('kecamatan_id', $id)
                ->exists();

            if ($digunakan) {
                return redirect()
                    ->route('admin.pengiriman.dataMaster')
                    ->with(
                        'error',
                        'Kecamatan tidak dapat dihapus karena masih digunakan oleh data kelurahan.'
                    );
            }

            // Hapus kecamatan jika tidak memiliki kelurahan
            DB::table('kecamatan')
                ->where('id', $id)
                ->delete();

            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'success',
                    'Data kecamatan berhasil dihapus.'
                );
        });
    }

    public function storeKelurahan(Request $request)
    {
        $validated = $request->validate([
            'kecamatan_id' => [
                'required',
                'integer',
                'exists:kecamatan,id',
            ],
            'nama_kelurahan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $namaKelurahan = trim($validated['nama_kelurahan']);

        // Periksa apakah kelurahan sudah terdaftar
        $sudahAda = DB::table('kelurahan')
            ->where('kecamatan_id', $validated['kecamatan_id'])
            ->where('nama_kelurahan', $namaKelurahan)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kelurahan tersebut sudah terdaftar pada kecamatan yang dipilih.'
                );
        }

        // Simpan data kelurahan
        DB::table('kelurahan')->insert([
            'kecamatan_id' => $validated['kecamatan_id'],
            'nama_kelurahan' => $namaKelurahan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with(
                'success',
                'Data kelurahan berhasil ditambahkan.'
            );
    }

    public function updateKelurahan(Request $request, $id)
    {
        $kelurahan = DB::table('kelurahan')
            ->where('id', $id)
            ->first();

        if (!$kelurahan) {
            abort(404);
        }

        $validated = $request->validate([
            'kecamatan_id' => [
                'required',
                'integer',
                'exists:kecamatan,id',
            ],
            'nama_kelurahan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $namaKelurahan = trim($validated['nama_kelurahan']);

        // Periksa apakah kelurahan sudah terdaftar
        $sudahAda = DB::table('kelurahan')
            ->where('kecamatan_id', $validated['kecamatan_id'])
            ->where('nama_kelurahan', $namaKelurahan)
            ->where('id', '!=', $id)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kelurahan tersebut sudah terdaftar pada kecamatan yang dipilih.'
                );
        }

        // Perbarui data kelurahan
        DB::table('kelurahan')
            ->where('id', $id)
            ->update([
                'kecamatan_id' => $validated['kecamatan_id'],
                'nama_kelurahan' => $namaKelurahan,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with(
                'success',
                'Data kelurahan berhasil diperbarui.'
            );
    }

    public function destroyKelurahan($id)
    {
        return DB::transaction(function () use ($id) {

            // Cari data kelurahan berdasarkan ID
            $kelurahan = DB::table('kelurahan')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$kelurahan) {
                abort(404);
            }

            // Periksa apakah kelurahan memiliki data ongkir
            $digunakan = DB::table('ongkir')
                ->where('kelurahan_id', $id)
                ->exists();

            if ($digunakan) {
                return redirect()
                    ->route('admin.pengiriman.dataMaster')
                    ->with(
                        'error',
                        'Kelurahan tidak dapat dihapus karena masih digunakan oleh data ongkir.'
                    );
            }

            // Hapus kelurahan jika tidak memiliki data ongkir
            DB::table('kelurahan')
                ->where('id', $id)
                ->delete();

            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'success',
                    'Data kelurahan berhasil dihapus.'
                );
        });
    }

    public function storeOngkir(Request $request)
    {
        $validated = $request->validate([
            'kelurahan_id' => [
                'required',
                'integer',
                'exists:kelurahan,id',
            ],
            'jarak_km' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'biaya_ongkir' => [
                'required',
                'regex:/\A[0-9]{1,10}(?:\.0{1,2})?\z/',
            ],
        ]);

        // Periksa apakah kelurahan sudah memiliki tarif ongkir
        $sudahAda = DB::table('ongkir')
            ->where('kelurahan_id', $validated['kelurahan_id'])
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kelurahan tersebut sudah memiliki data ongkir.'
                );
        }

        // Simpan data ongkir
        DB::table('ongkir')->insert([
            'kelurahan_id' => $validated['kelurahan_id'],
            'jarak_km' => $validated['jarak_km'],
            'biaya_ongkir' => $validated['biaya_ongkir'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with(
                'success',
                'Data ongkir berhasil ditambahkan.'
            );
    }

    public function updateOngkir(Request $request, $id)
    {
        // Cari data ongkir berdasarkan ID
        $ongkir = DB::table('ongkir')
            ->where('id', $id)
            ->first();

        if (!$ongkir) {
            abort(404);
        }

        // Validasi input
        $validated = $request->validate([
            'kelurahan_id' => [
                'required',
                'integer',
                'exists:kelurahan,id',
            ],
            'jarak_km' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'biaya_ongkir' => [
                'required',
                'regex:/\A[0-9]{1,10}(?:\.0{1,2})?\z/',
            ],
        ]);

        // Periksa apakah kelurahan sudah digunakan oleh data ongkir lain
        $sudahAda = DB::table('ongkir')
            ->where('kelurahan_id', $validated['kelurahan_id'])
            ->where('id', '!=', $id)
            ->exists();

        if ($sudahAda) {
            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'error',
                    'Kelurahan tersebut sudah memiliki data ongkir.'
                );
        }

        // Perbarui data ongkir
        DB::table('ongkir')
            ->where('id', $id)
            ->update([
                'kelurahan_id' => $validated['kelurahan_id'],
                'jarak_km' => $validated['jarak_km'],
                'biaya_ongkir' => $validated['biaya_ongkir'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.pengiriman.dataMaster')
            ->with(
                'success',
                'Data ongkir berhasil diperbarui.'
            );
    }

    public function destroyOngkir($id)
    {
        return DB::transaction(function () use ($id) {

            // Cari data ongkir berdasarkan ID
            $ongkir = DB::table('ongkir')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$ongkir) {
                abort(404);
            }

            // Hapus data ongkir
            DB::table('ongkir')
                ->where('id', $id)
                ->delete();

            return redirect()
                ->route('admin.pengiriman.dataMaster')
                ->with(
                    'success',
                    'Data ongkir berhasil dihapus.'
                );
        });
    }
}
