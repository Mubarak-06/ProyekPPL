<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenjualanKategoriSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithStrictNullComparison
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
            ['PENJUALAN PER KATEGORI'],
            ['Periode', $this->data['tanggalAwal'] . ' s.d. ' . $this->data['tanggalAkhir']],
            ['Kategori', $this->data['namaKategori']],
            [''],
            ['No.', 'Kategori Produk', 'Produk Terjual', 'Total Penjualan'],
        ];

        foreach ($this->data['penjualanKategori'] as $index => $item) {
            $rows[] = [
                $index + 1,
                $item->nama_kategori,
                (int) ($item->jumlah_terjual ?? 0),
                (float) ($item->total_penjualan ?? 0),
            ];
        }

        $rows[] = [
            '',
            'TOTAL',
            (int) $this->data['penjualanKategori']->sum('jumlah_terjual'),
            (float) $this->data['penjualanKategori']->sum('total_penjualan'),
        ];

        return $rows;
    }

    public function title(): string
    {
        return 'Penjualan per Kategori';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = 7 + $this->data['penjualanKategori']->count();

        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');

        $sheet->getStyle('A1:D2')->getFont()->setBold(true);
        $sheet->getStyle('A1:D2')->getFont()->setSize(14);

        $sheet->getStyle('A6:D6')->getFont()->setBold(true);
        $sheet->getStyle('A6:D6')->getFill()
            ->setFillType('solid')
            ->getStartColor()->setRGB('008A50');

        $sheet->getStyle('A6:D6')->getFont()
            ->getColor()->setRGB('FFFFFF');

        $sheet->getStyle("D7:D{$lastRow}")
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');

        $sheet->getStyle("A6:D{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle('thin');

        $sheet->getStyle("A{$lastRow}:D{$lastRow}")
            ->getFont()->setBold(true);

        return [];
    }
}