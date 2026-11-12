<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AkunController extends Controller
{
    public function profil(Request $request)
    {
        $user = $request->user();

        return view('pelanggan.akun.profil', compact('user'));
    }

    public function updateProfil(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'nomor_telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\s-]{8,20}$/'],
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        unset($data['foto_profil']);
        $user = $request->user();
        $fotoLama = $user->foto_profil;
        $fotoBaru = null;
        if ($request->hasFile('foto_profil')) {
            $fotoBaru = $request->file('foto_profil')->store('profil', 'public');
            if (!$fotoBaru) {
                throw ValidationException::withMessages(['foto_profil' => 'Foto gagal disimpan.']);
            }
            $user->foto_profil = $fotoBaru;
        }
        if (array_key_exists('nomor_telepon', $data)) {
            $user->nomor_telepon = $data['nomor_telepon'];
        }
        unset($data['nomor_telepon']);
        if ($data['email'] !== $request->user()->email) {
            $request->user()->email_verified_at = null;
        }
        try {
            $user->fill($data)->save();
        } catch (\Throwable $e) {
            if ($fotoBaru) {
                Storage::disk('public')->delete($fotoBaru);
            }
            throw $e;
        }
        if ($fotoBaru && $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        return redirect()->route('pelanggan.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function editProfil(Request $request)
    {
        return view('pelanggan.akun.edit-profil', ['user' => $request->user()]);
    }

    public function alamat(Request $request)
    {
        $alamat = Alamat::with('kelurahan.ongkir')->where('user_id', $request->user()->id)->latest()->get();

        return view('pelanggan.akun.alamat', compact('alamat'));
    }

    public function formAlamat(Request $request, $id = null)
    {
        $alamat = $id ? Alamat::where('user_id', $request->user()->id)->findOrFail($id) : new Alamat;
        $kelurahan = DB::table('kelurahan')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->join('kota', 'kecamatan.kota_id', '=', 'kota.id')
            ->join('provinsi', 'kota.provinsi_id', '=', 'provinsi.id')
            ->select('kelurahan.id', 'kelurahan.kecamatan_id', 'kecamatan.kota_id', 'kota.provinsi_id', 'nama_kelurahan', 'nama_kecamatan', 'nama_kota', 'nama_provinsi')
            ->orderBy('nama_kelurahan')->get();

        $provinsi = DB::table('provinsi')->orderBy('nama_provinsi')->get();
        $wilayah = $kelurahan->firstWhere('id', old('kelurahan_id', $alamat->kelurahan_id));

        return view('pelanggan.akun.form-alamat', compact('alamat', 'kelurahan', 'provinsi', 'wilayah'));
    }

    public function simpanAlamat(Request $request, $id = null)
    {
        $alamat = $id ? Alamat::where('user_id', $request->user()->id)->findOrFail($id) : new Alamat;
        $data = $request->validate([
            'nama_penerima' => 'required|string|max:100',
            'nomor_telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\s-]{8,20}$/'],
            'alamat_lengkap' => 'required|string|max:2000',
            'kelurahan_id' => 'required|integer|exists:kelurahan,id',
            'kode_pos' => ['required', 'regex:/^[0-9]{5}$/'],
        ]);
        $alamat->fill($data);
        $alamat->user_id = $request->user()->id;
        $alamat->save();

        return redirect()->route('pelanggan.alamat')->with('success', 'Alamat berhasil disimpan.');
    }

    public function pesanan(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'per_page' => ['nullable', Rule::in([10, 25, 50])]]);
        $pesanan = Pesanan::with('pembayaran', 'pengiriman', 'detailPesanan')->where('user_id', $request->user()->id)
            ->when($data['q'] ?? null, fn ($query, $q) => $query->where('kode_pesanan', 'like', '%'.$q.'%'))
            ->latest()->paginate($data['per_page'] ?? 10)->withQueryString();

        return view('pelanggan.akun.pesanan', compact('pesanan'));
    }

    public function hapusAlamat(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $alamat = Alamat::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
            if ($alamat->pesanan()->exists()) {
                return back()->with('error', 'Alamat sudah digunakan pada pesanan dan tidak dapat dihapus.');
            }
            $alamat->delete();
            return back()->with('success', 'Alamat berhasil dihapus.');
        });
    }

    public function wilayah(Request $request)
    {
        $data = $request->validate(['jenis' => ['required', Rule::in(['kota', 'kecamatan', 'kelurahan'])], 'induk' => 'required|integer|min:1']);
        $induk = ['kota' => 'provinsi_id', 'kecamatan' => 'kota_id', 'kelurahan' => 'kecamatan_id'];
        return response()->json(DB::table($data['jenis'])->where($induk[$data['jenis']], $data['induk'])
            ->orderBy('nama_'.$data['jenis'])->get(['id', 'nama_'.$data['jenis'].' as nama']));
    }

    public function pembayaran(Request $request, $id)
    {
        $pesanan = Pesanan::with('pembayaran', 'pengiriman')->where('user_id', $request->user()->id)->findOrFail($id);
        $metode = $pesanan->pembayaran ? DB::table('metode_pembayaran')->find($pesanan->pembayaran->metode_pembayaran_id) : null;
        return view('pelanggan.pembayaran', compact('pesanan', 'metode'));
    }

    public function detailPesanan(Request $request, $id)
    {
        $pesanan = Pesanan::with('detailPesanan', 'pembayaran', 'pengiriman')
            ->where('user_id', $request->user()->id)->findOrFail($id);
        $metode = $pesanan->pembayaran
            ? DB::table('metode_pembayaran')->find($pesanan->pembayaran->metode_pembayaran_id)
            : null;

        return view('pelanggan.akun.detail-pesanan', compact('pesanan', 'metode'));
    }

    public function unggahBukti(Request $request, $id)
    {
        $request->validate(['bukti' => 'required|image|mimes:jpg,jpeg,png|max:2048']);
        $path = null;

        try {
            DB::transaction(function () use ($request, $id, &$path) {
                $pesanan = Pesanan::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
                $pembayaran = $pesanan->pembayaran()->lockForUpdate()->firstOrFail();
                abort_unless(in_array($pesanan->status_pesanan, ['Menunggu', 'Diproses'], true)
                    && $pembayaran->status_pembayaran === 'Menunggu'
                    && !$pembayaran->bukti_pembayaran, 422, 'Bukti pembayaran tidak dapat diunggah untuk pesanan ini.');
                $path = $request->file('bukti')->store('bukti-pembayaran', 'public');
                if (!$path) {
                    throw ValidationException::withMessages(['bukti' => 'Bukti pembayaran gagal disimpan. Silakan coba kembali.']);
                }
                $pembayaran->update(['bukti_pembayaran' => $path]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu pemeriksaan admin.');
    }
}
