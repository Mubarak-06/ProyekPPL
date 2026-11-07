<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    public function index()
    {
        $pembayaran = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.pesanan_id', '=', 'pesanan.id')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->leftJoin(
                'metode_pembayaran',
                'pembayaran.metode_pembayaran_id',
                '=',
                'metode_pembayaran.id'
            )
            ->select(
                'pembayaran.id',
                'pembayaran.pesanan_id',
                'pembayaran.metode_pembayaran',
                'pembayaran.jumlah_pembayaran',
                'pembayaran.status_pembayaran',
                'pembayaran.waktu_pembayaran',
                'pembayaran.created_at',
                'pesanan.kode_pesanan',
                'users.name as nama_pelanggan',
                'users.email as email_pelanggan',
                'metode_pembayaran.nama_penyedia'
            )
            ->orderBy('pembayaran.created_at', 'desc')
            ->get();

        return view('admin.pembayaran.index', compact('pembayaran'));
    }

    public function show($id)
    {
        $pembayaran = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.pesanan_id', '=', 'pesanan.id')
            ->join('users', 'pesanan.user_id', '=', 'users.id')
            ->leftJoin(
                'metode_pembayaran',
                'pembayaran.metode_pembayaran_id',
                '=',
                'metode_pembayaran.id'
            )
            ->where('pembayaran.id', $id)
            ->select(
                'pembayaran.*',
                'pesanan.kode_pesanan',
                'pesanan.subtotal',
                'pesanan.ongkos_kirim',
                'pesanan.total_pembayaran',
                'pesanan.status_pesanan',
                'pesanan.created_at as tanggal_pesanan',
                'users.name as nama_pelanggan',
                'users.email as email_pelanggan',
                'metode_pembayaran.nama_penyedia',
                'metode_pembayaran.nomor_rekening',
                'metode_pembayaran.nama_pemilik',
                'metode_pembayaran.logo',
                'metode_pembayaran.gambar_qris'
            )
            ->first();

        if (!$pembayaran) {
            abort(404);
        }

        return view('admin.pembayaran.show', compact('pembayaran'));
    }

    public function metode()
    {
        $metodePembayaran = DB::table('metode_pembayaran')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.pembayaran.metode', compact('metodePembayaran'));
    }

    public function storeMetode(Request $request)
    {
        $data = $request->validate([
            'jenis_metode' => [
                'required',
                Rule::in(['transfer_bank', 'ewallet', 'qris'])
            ],
            'nama_penyedia' => [
                'required',
                'string',
                'max:100'
            ],
            'nomor_rekening' => [
                'nullable',
                'string',
                'max:50'
            ],
            'nama_pemilik' => [
                'nullable',
                'string',
                'max:150'
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'max:2048'
            ],
            'gambar_qris' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'max:2048'
            ],
            'status' => [
                'required',
                Rule::in(['aktif', 'nonaktif'])
            ]
        ]);

        // Validasi berdasarkan jenis metode
        $penyedia = [
            'transfer_bank' => ['BCA', 'BRI', 'BNI', 'Mandiri'],
            'ewallet' => ['DANA'],
            'qris' => ['QRIS']
        ];

        if (
            !in_array(
                $data['nama_penyedia'],
                $penyedia[$data['jenis_metode']],
                true
            )
        ) {
            return back()
                ->withErrors([
                    'nama_penyedia' => 'Penyedia tidak sesuai dengan jenis pembayaran.'
                ])
                ->withInput();
        }

        if ($data['jenis_metode'] !== 'qris') {
            $request->validate([
                'nomor_rekening' => 'required|string|max:50',
                'nama_pemilik' => 'required|string|max:150'
            ]);
        } else {
            $request->validate([
                'gambar_qris' => 'required|image|mimes:png,jpg,jpeg|max:2048'
            ]);
        }

        // Mencegah metode pembayaran yang sama tersimpan dua kali
        $jenis = $data['jenis_metode'];

        if ($jenis === 'transfer_bank' || $jenis === 'ewallet') {

            $sudahAda = DB::table('metode_pembayaran')
                ->where('jenis_metode', $jenis)
                ->where('nama_penyedia', $data['nama_penyedia'])
                ->where('nomor_rekening', $data['nomor_rekening'] ?? null)
                ->exists();

            if ($sudahAda) {
                return back()
                    ->withErrors([
                        'nomor_rekening' => 'Nomor rekening atau DANA sudah terdaftar pada metode pembayaran ini.'
                    ])
                    ->withInput();
            }

        } elseif ($jenis === 'qris') {

            $sudahAda = DB::table('metode_pembayaran')
                ->where('jenis_metode', 'qris')
                ->exists();

            if ($sudahAda) {
                return back()
                    ->withErrors([
                        'jenis_metode' => 'Metode QRIS sudah terdaftar. Silakan edit data QRIS yang tersedia.'
                    ])
                    ->withInput();
            }
        }

        // Simpan file logo jika diunggah
        $logo = $request->hasFile('logo')
            ? $request->file('logo')->store('metode-pembayaran/logo', 'public')
            : null;

        // Simpan gambar QRIS jika diunggah
        $gambarQRIS = $request->hasFile('gambar_qris')
            ? $request->file('gambar_qris')->store('metode-pembayaran/qris', 'public')
            : null;

        DB::table('metode_pembayaran')->insert([
            'jenis_metode' => $data['jenis_metode'],
            'nama_penyedia' => $data['nama_penyedia'],
            'nomor_rekening' => $data['jenis_metode'] === 'qris'
                ? null
                : $data['nomor_rekening'],
            'nama_pemilik' => $data['jenis_metode'] === 'qris'
                ? null
                : $data['nama_pemilik'],
            'logo' => $logo,
            'gambar_qris' => $data['jenis_metode'] === 'qris'
                ? $gambarQRIS
                : null,
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()
            ->route('admin.pembayaran.metode')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function editMetode($id)
    {
        // Mengambil metode pembayaran yang akan diedit
        $metode = DB::table('metode_pembayaran')
            ->where('id', $id)
            ->first();

        if (!$metode) {
            abort(404);
        }

        // Mengambil seluruh metode pembayaran untuk tabel
        $metodePembayaran = DB::table('metode_pembayaran')
            ->orderBy('id', 'desc')
            ->get();

        // Tetap menggunakan halaman metode.blade.php
        return view('admin.pembayaran.metode', compact(
            'metodePembayaran',
            'metode'
        ));
    }

    public function updateMetode(Request $request, $id)
    {
        $metode = DB::table('metode_pembayaran')
            ->where('id', $id)
            ->first();

        if (!$metode) {
            abort(404);
        }

        $data = $request->validate([
            'nama_penyedia' => [
                'required',
                'string',
                'max:100'
            ],
            'nomor_rekening' => [
                'nullable',
                'string',
                'max:50'
            ],
            'nama_pemilik' => [
                'nullable',
                'string',
                'max:150'
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'max:2048'
            ],
            'gambar_qris' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg',
                'max:2048'
            ],
            'status' => [
                'required',
                Rule::in(['aktif', 'nonaktif'])
            ]
        ]);

        // Jenis pembayaran tidak diubah saat edit.
        $jenis = $metode->jenis_metode;

        $penyedia = [
            'transfer_bank' => ['BCA', 'BRI', 'BNI', 'Mandiri'],
            'ewallet' => ['DANA'],
            'qris' => ['QRIS']
        ];

        if (!in_array($data['nama_penyedia'], $penyedia[$jenis], true)) {
            return back()
                ->withErrors([
                    'nama_penyedia' => 'Penyedia tidak sesuai dengan jenis pembayaran.'
                ])
                ->withInput();
        }

        if ($jenis !== 'qris') {
            $request->validate([
                'nomor_rekening' => 'required|string|max:50',
                'nama_pemilik' => 'required|string|max:150'
            ]);

            $duplikat = DB::table('metode_pembayaran')
                ->where('jenis_metode', $jenis)
                ->where('nama_penyedia', $data['nama_penyedia'])
                ->where('nomor_rekening', $data['nomor_rekening'])
                ->where('id', '!=', $id)
                ->exists();

            if ($duplikat) {
                return back()
                    ->withErrors([
                        'nomor_rekening' => 'Nomor rekening atau DANA sudah digunakan.'
                    ])
                    ->withInput();
            }
        }

        $perubahan = [
            'nama_penyedia' => $data['nama_penyedia'],
            'nomor_rekening' => $jenis === 'qris'
                ? null
                : $data['nomor_rekening'],
            'nama_pemilik' => $jenis === 'qris'
                ? null
                : $data['nama_pemilik'],
            'status' => $data['status'],
            'updated_at' => now()
        ];

        $logoBaru = null;
        $qrisBaru = null;

        if ($request->hasFile('logo')) {
            $logoBaru = $request->file('logo')
                ->store('metode-pembayaran/logo', 'public');

            $perubahan['logo'] = $logoBaru;
        }

        if ($jenis === 'qris' && $request->hasFile('gambar_qris')) {
            $qrisBaru = $request->file('gambar_qris')
                ->store('metode-pembayaran/qris', 'public');

            $perubahan['gambar_qris'] = $qrisBaru;
        }

        try {
            DB::table('metode_pembayaran')
                ->where('id', $id)
                ->update($perubahan);
        } catch (\Throwable $e) {

            if ($logoBaru) {
                Storage::disk('public')->delete($logoBaru);
            }

            if ($qrisBaru) {
                Storage::disk('public')->delete($qrisBaru);
            }

            throw $e;
        }

        if ($logoBaru && $metode->logo) {
            Storage::disk('public')->delete($metode->logo);
        }

        if ($qrisBaru && $metode->gambar_qris) {
            Storage::disk('public')->delete($metode->gambar_qris);
        }

        return redirect()
            ->route('admin.pembayaran.metode')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroyMetode($id)
    {
        // Mengambil data metode pembayaran
        $metode = DB::table('metode_pembayaran')
            ->where('id', $id)
            ->first();

        if (!$metode) {
            abort(404);
        }

        // Memastikan metode belum digunakan dalam transaksi
        $digunakan = DB::table('pembayaran')
            ->where('metode_pembayaran_id', $id)
            ->exists();

        if ($digunakan) {
            return redirect()
                ->route('admin.pembayaran.metode')
                ->with(
                    'error',
                    'Metode pembayaran tidak dapat dihapus karena sudah digunakan dalam transaksi. Silakan nonaktifkan metode tersebut.'
                );
        }

        // Menghapus data dari database
        try {
            DB::table('metode_pembayaran')
                ->where('id', $id)
                ->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('admin.pembayaran.metode')
                ->with(
                    'error',
                    'Metode pembayaran tidak dapat dihapus karena masih memiliki data yang berkaitan.'
                );
        }

        // Menghapus file logo apabila tersedia
        if ($metode->logo) {
            Storage::disk('public')->delete($metode->logo);
        }

        // Menghapus gambar QRIS apabila tersedia
        if ($metode->gambar_qris) {
            Storage::disk('public')->delete($metode->gambar_qris);
        }

        return redirect()
            ->route('admin.pembayaran.metode')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}