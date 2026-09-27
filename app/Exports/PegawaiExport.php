<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export daftar pegawai ke Excel (mengikuti filter aktif di halaman index).
 *
 * Susunan sheet:
 *   baris 1 : judul
 *   baris 2 : keterangan (tanggal & filter)
 *   baris 3 : kosong
 *   baris 4 : header kolom
 *   baris 5+: data
 */
class PegawaiExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithCustomValueBinder,
    WithStyles,
    WithEvents,
    WithTitle,
    ShouldAutoSize
{
    private const BARIS_HEADER = 4;
    private const KOLOM_TERAKHIR = 'M';

    private int $no = 0;

    public function __construct(
        private Collection $pegawais,
        private array $filter,
        private Carbon $dicetak,
    ) {}

    public function collection(): Collection
    {
        return $this->pegawais;
    }

    public function title(): string
    {
        return 'Data Pegawai';
    }

    public function headings(): array
    {
        return [
            ['DAFTAR PEGAWAI NEGERI SIPIL'],
            [$this->keterangan()],
            [''],
            [
                'No', 'Nama', 'NIP', 'Gol. CPNS', 'Gol. Terakhir', 'TMT Pangkat Terakhir',
                'Jabatan', 'TMT CPNS', 'Masa Kerja', 'KGB Terakhir', 'KGB Berikutnya',
                'Status KGB', 'Mode',
            ],
        ];
    }

    public function map($p): array
    {
        return [
            ++$this->no,
            $p->nama,
            $p->nip,
            $p->gol_pangkat_cpns,
            $p->gol_pangkat_terakhir_label,
            $this->tgl($p->tmt_pangkat_terakhir),
            $p->jabatan,
            $this->tgl($p->tmt_cpns),
            $p->masa_kerja_label,
            $this->tgl($p->kgb),
            $this->tgl($p->kgb_berikutnya),
            $this->statusLabel($p->status_kgb),
            $p->kgb_mode === 'manual' ? 'Manual' : 'Otomatis',
        ];
    }

    /** Tanggal disimpan sebagai tanggal Excel asli, jadi bisa diurutkan/difilter. */
    public function columnFormats(): array
    {
        return [
            'F' => 'dd-mm-yyyy',
            'H' => 'dd-mm-yyyy',
            'J' => 'dd-mm-yyyy',
            'K' => 'dd-mm-yyyy',
        ];
    }

    /** NIP (18 digit) dipaksa jadi teks supaya tidak berubah jadi 1,98E+17. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'C' && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'color' => ['rgb' => '444444']]],
            'A'.self::BARIS_HEADER.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $k = self::KOLOM_TERAKHIR;
                $h = self::BARIS_HEADER;
                $barisAkhir = max($h, $sheet->getHighestRow());

                $sheet->mergeCells("A1:{$k}1");
                $sheet->mergeCells("A2:{$k}2");
                $sheet->freezePane('A'.($h + 1));

                $sheet->getStyle("A{$h}:{$k}{$barisAkhir}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                if ($this->pegawais->isEmpty()) {
                    $sheet->setCellValue('A'.($h + 1), 'Tidak ada data.');
                    $sheet->mergeCells('A'.($h + 1).":{$k}".($h + 1));

                    return;
                }

                $sheet->setAutoFilter("A{$h}:{$k}{$barisAkhir}");

                // Tandai merah KGB yang terlewat / jatuh tempo bulan ini (seperti versi PDF).
                foreach ($this->pegawais->values() as $i => $p) {
                    if (in_array($p->status_kgb, ['terlewat', 'bulan_ini'], true)) {
                        $baris = $h + 1 + $i;
                        $sheet->getStyle("K{$baris}:L{$baris}")->getFont()
                            ->setBold(true)->getColor()->setRGB('B91C1C');
                    }
                }
            },
        ];
    }

    private function tgl($tanggal): ?float
    {
        return $tanggal ? ExcelDate::dateTimeToExcel($tanggal) : null;
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'terlewat' => 'Terlewat',
            'bulan_ini' => 'Jatuh tempo bulan ini',
            '3_bulan' => 'Jatuh tempo ≤ 3 bulan',
            default => '-',
        };
    }

    private function keterangan(): string
    {
        $bagian = ['Keadaan per '.$this->dicetak->translatedFormat('d F Y')];

        if (! empty($this->filter['golongan'])) {
            $bagian[] = 'Golongan '.$this->filter['golongan'];
        }

        $bagian[] = match ($this->filter['kgb'] ?? '') {
            'bulan_ini' => 'KGB jatuh tempo bulan ini',
            '3_bulan' => 'KGB jatuh tempo ≤ 3 bulan',
            'terlewat' => 'KGB terlewat',
            default => null,
        };

        if (($this->filter['mode'] ?? '') !== '') {
            $bagian[] = 'Mode '.ucfirst($this->filter['mode']);
        }

        if (! empty($this->filter['q'])) {
            $bagian[] = 'Pencarian: "'.$this->filter['q'].'"';
        }

        return implode(' · ', array_filter($bagian));
    }
}
