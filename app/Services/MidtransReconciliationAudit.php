<?php

namespace App\Services;

use App\Models\MidtransRekonsiliasi;
use App\Models\MidtransRekonsiliasiRiwayat;
use Carbon\CarbonImmutable;
use LogicException;

class MidtransReconciliationAudit
{
    public function recordSystemCase(MidtransRekonsiliasi $case, string $source, bool $verified): void
    {
        if (! in_array($source, ['webhook', 'coordinator'], true)
            || $case->getConnection()->transactionLevel() === 0) {
            throw new LogicException('Audit sistem harus dicatat dalam transaksi kasus.');
        }

        $time = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s.u');
        $key = hash('sha256', 'case:'.$source);
        $history = MidtransRekonsiliasiRiwayat::on($case->getConnectionName());
        if ((clone $history)->where('rekonsiliasi_id', $case->getKey())->where('kunci_idempotensi', $key)->exists()) {
            return;
        }
        $history->insert([
            'rekonsiliasi_id' => $case->getKey(),
            'jenis_tindakan' => 'tahan',
            'waktu_tindakan' => $time,
            'jenis_aktor' => 'sistem',
            'proses_sistem' => $source,
            'hasil_pemeriksaan' => $verified ? 'respons_tersedia' : 'tidak_pasti',
            'sumber_bukti' => $verified ? 'verifikasi_gateway' : 'catatan_lokal',
            'keputusan_operasional' => 'tetap_ditahan',
            'alasan_keputusan' => 'kasus_perlu_pemeriksaan',
            'kunci_idempotensi' => $key,
            'created_at' => $time,
        ]);
    }
}
