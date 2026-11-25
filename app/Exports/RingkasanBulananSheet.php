<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RingkasanBulananSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithStrictNullComparison
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
            ['RINGKASAN PENJUALAN PER BULAN'],
            ['Periode', $this->data['tanggalAwal'] . ' s.d. ' . $this->data['tanggalAkhir']],
            ['Kategori', $this->data['namaKategori']],
            [''],
            ['Bulan', 'Jumlah Pesanan', 'Produk Terjual', 'Total Penjualan'],
        ];

        foreach ($this->data['ringkasanBulanan'] as $item) {
            $rows[] = [
                $item->bulan,
                (int) ($item->jumlah_pesanan ?? 0),
                (int) ($item->produk_terjual ?? 0),
                (float) ($item->total_penjualan ?? 0),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Ringkasan Bulanan';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = 6 + $this->data['ringkasanBulanan']->count();

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

        return [];
    }
}