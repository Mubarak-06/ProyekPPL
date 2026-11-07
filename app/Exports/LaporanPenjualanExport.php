<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanPenjualanExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new RingkasanPenjualanSheet($this->data),
            new PenjualanKategoriSheet($this->data),
            new ProdukTerlarisSheet($this->data),
            new RingkasanBulananSheet($this->data),
        ];
    }
}