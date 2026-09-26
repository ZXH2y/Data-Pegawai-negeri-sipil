<?php

namespace App\Console\Commands;

use App\Models\Pegawai;
use App\Services\KgbService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class UpdateKgbPegawai extends Command
{
    protected $signature = 'pegawai:update-kgb
                            {--tanggal= : Tanggal acuan (format Y-m-d). Default: hari ini}
                            {--dry-run : Hanya tampilkan yang akan diubah, tanpa menyimpan}';

    protected $description = 'Update otomatis KGB pegawai yang sudah jatuh tempo (pegawai mode manual dilewati)';

    public function handle(KgbService $service): int
    {
        try {
            $tanggal = $this->option('tanggal')
                ? Carbon::createFromFormat('Y-m-d', $this->option('tanggal'))->startOfDay()
                : now()->startOfDay();
        } catch (Throwable) {
            $this->error('Format --tanggal tidak valid. Gunakan Y-m-d, contoh: 2026-10-01');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%sMemproses KGB per %s ...',
            $dryRun ? '[DRY RUN] ' : '',
            $tanggal->format('d-m-Y'),
        ));

        $hasil = $service->prosesSemua($tanggal, $dryRun, function (Pegawai $p, int $jumlah) {
            $this->line("  • {$p->nip}  {$p->nama}  (+{$jumlah} KGB)");
        });

        $this->newLine();
        $this->table(
            ['Diperiksa', 'Pegawai diperbarui', 'Total KGB diterapkan'],
            [[$hasil['diperiksa'], $hasil['pegawai_diperbarui'], $hasil['total_kgb']]],
        );

        return self::SUCCESS;
    }
}
