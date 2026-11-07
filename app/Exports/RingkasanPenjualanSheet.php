<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RingkasanPenjualanSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            ['CV KARUNIA ABADI'],
            ['LAPORAN RINGKASAN PENJUALAN'],
            ['Periode', $this->data['tanggalAwal'] . ' s.d. ' . $this->data['tanggalAkhir']],
            ['Kategori', $this->data['namaKategori']],
            [''],
            ['No.', 'Keterangan', 'Jumlah'],
            [1, 'Total Penjualan', (float) $this->data['totalPenjualan']],
            [2, 'Jumlah Pesanan', (int) $this->data['jumlahPesanan']],
            [3, 'Produk Terjual', (int) $this->data['produkTerjual']],
            [4, 'Pesanan Selesai', (int) $this->data['pesananSelesai']],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Penjualan';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');

        $sheet->getStyle('A1:C2')->getFont()->setBold(true);
        $sheet->getStyle('A1:C2')->getFont()->setSize(14);

        $sheet->getStyle('A6:C6')->getFont()->setBold(true);
        $sheet->getStyle('A6:C6')->getFill()
            ->setFillType('solid')
            ->getStartColor()->setRGB('008A50');

        $sheet->getStyle('A6:C6')->getFont()
            ->getColor()->setRGB('FFFFFF');

        $sheet->getStyle('C7')->getNumberFormat()
            ->setFormatCode('"Rp" #,##0');

        $sheet->getStyle('A6:C10')
            ->getBorders()->getAllBorders()
            ->setBorderStyle('thin');

        return [];
    }
}