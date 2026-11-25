<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdukTerlarisSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithStrictNullComparison
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [
            ['CV KARUNIA ABADI'],
            ['LAPORAN PRODUK TERLARIS'],
            ['Periode', $this->data['tanggalAwal'] . ' s.d. ' . $this->data['tanggalAkhir']],
            ['Kategori', $this->data['namaKategori']],
            [''],
            ['No.', 'Nama Produk', 'Kategori', 'Jumlah Terjual', 'Total Penjualan'],
        ];

        foreach ($this->data['produkTerlaris'] as $index => $item) {
            $rows[] = [
                $index + 1,
                $item->nama_produk,
                $item->nama_kategori,
                (int) $item->jumlah_terjual,
                (float) $item->total_penjualan,
            ];
        }

        if ($this->data['produkTerlaris']->isEmpty()) {
            $rows[] = ['', 'Belum ada produk terjual.'];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Produk Terlaris';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(7, 6 + $this->data['produkTerlaris']->count());

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        $sheet->getStyle('A1:E2')->getFont()->setBold(true);
        $sheet->getStyle('A1:E2')->getFont()->setSize(14);

        $sheet->getStyle('A6:E6')->getFont()->setBold(true);
        $sheet->getStyle('A6:E6')->getFill()
            ->setFillType('solid')
            ->getStartColor()->setRGB('008A50');

        $sheet->getStyle('A6:E6')->getFont()
            ->getColor()->setRGB('FFFFFF');

        $sheet->getStyle("E7:E{$lastRow}")
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');

        $sheet->getStyle("A6:E{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle('thin');

        return [];
    }
}