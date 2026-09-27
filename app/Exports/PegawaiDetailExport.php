<?php

namespace App\Exports;

use App\Models\Pegawai;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** Export detail satu pegawai beserta riwayat KGB-nya ke Excel. */
class PegawaiDetailExport extends DefaultValueBinder implements
    FromArray,
    WithCustomValueBinder,
    WithEvents,
    WithTitle,
    ShouldAutoSize
{
    /** Nomor baris penting, diisi saat array() disusun, dipakai saat styling. */
    private array $barisJudulBagian = [];
    private int $barisHeaderRiwayat = 0;
    private int $barisAkhirRiwayat = 0;

    public function __construct(
        private Pegawai $pegawai,
        private Carbon $dicetak,
    ) {}

    public function title(): string
    {
        return 'NIP '.$this->pegawai->nip;
    }

    public function array(): array
    {
        $p = $this->pegawai;
        $tgl = fn ($d) => $d?->translatedFormat('d F Y') ?? '-';

        $rows = [
            ['DATA PEGAWAI NEGERI SIPIL'],
            [''],
            ['A. Data Kepegawaian'],
            ['Nama', ':', $p->nama],
            ['NIP', ':', $p->nip],
            ['Jabatan', ':', $p->jabatan],
            ['Gol/Pangkat CPNS', ':', $p->gol_pangkat_cpns_label],
            ['Gol/Pangkat terakhir', ':', $p->gol_pangkat_terakhir_label],
            ['TMT pangkat terakhir', ':', $tgl($p->tmt_pangkat_terakhir)],
            ['TMT CPNS', ':', $tgl($p->tmt_cpns)],
            ['Masa kerja', ':', $p->masa_kerja_label],
            [''],
            ['B. Kenaikan Gaji Berkala'],
            ['KGB terakhir', ':', $tgl($p->kgb)],
            ['KGB berikutnya', ':', $tgl($p->kgb_berikutnya)],
            ['Mode pembaruan', ':', $p->kgb_mode === 'manual' ? 'Manual' : 'Otomatis'],
            [''],
            ['C. Riwayat Perubahan KGB'],
            ['No', 'KGB Lama', 'KGB Baru', 'KGB Berikutnya', 'Sumber', 'Keterangan'],
        ];

        $this->barisJudulBagian = [3, 13, 18];
        $this->barisHeaderRiwayat = count($rows);

        foreach ($p->kgbHistories->values() as $i => $h) {
            $rows[] = [
                $i + 1,
                $h->kgb_lama?->format('d-m-Y') ?? '-',
                $h->kgb_baru->format('d-m-Y'),
                $h->kgb_berikutnya_baru->format('d-m-Y'),
                ucfirst($h->sumber),
                $h->keterangan ?? '-',
            ];
        }

        if ($p->kgbHistories->isEmpty()) {
            $rows[] = ['Belum ada riwayat.'];
        }

        $this->barisAkhirRiwayat = count($rows);

        $rows[] = [''];
        $rows[] = ['Dicetak '.$this->dicetak->format('d-m-Y H:i')];

        return $rows;
    }

    /** NIP (baris 5 kolom C) dipaksa jadi teks supaya tidak berubah jadi 1,98E+17. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getCoordinate() === 'C5') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $h = $this->barisHeaderRiwayat;
                $akhir = $this->barisAkhirRiwayat;

                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                foreach ($this->barisJudulBagian as $b) {
                    $sheet->getStyle("A{$b}")->getFont()->setBold(true);
                }

                $sheet->getStyle("A{$h}:F{$h}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                ]);
                $sheet->getStyle("A{$h}:F{$akhir}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$h}:E{$akhir}")->getAlignment()->setHorizontal('center');

                if ($this->pegawai->kgbHistories->isEmpty()) {
                    $sheet->mergeCells("A{$akhir}:F{$akhir}");
                }

                $sheet->getStyle('A'.($akhir + 2))->getFont()->setItalic(true)->setSize(8);
            },
        ];
    }
}
