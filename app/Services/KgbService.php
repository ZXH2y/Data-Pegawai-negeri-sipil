<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\PegawaiKgbHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Semua logika perubahan KGB dipusatkan di sini supaya Command (scheduler),
 * tombol "Jalankan sekarang", dan form update manual memakai aturan yang sama.
 */
class KgbService
{
    /**
     * Proses otomatis satu pegawai: selama KGB berikutnya sudah jatuh tempo (≤ $per),
     * geser KGB terakhir ke tanggal itu dan catat riwayatnya. Loop menangani
     * pegawai yang tertinggal lebih dari satu periode (mis. data lama baru diinput).
     *
     * Pegawai ber-mode manual DILEWATI.
     *
     * @return int jumlah KGB yang diterapkan
     */
    public function prosesOtomatis(Pegawai $pegawai, ?CarbonInterface $per = null, bool $dryRun = false): int
    {
        $per = Carbon::instance($per ?? now())->startOfDay();

        return DB::transaction(function () use ($pegawai, $per, $dryRun) {
            // Kunci baris supaya dua proses (cron + tombol manual) tidak dobel mencatat.
            $p = Pegawai::query()->whereKey($pegawai->getKey())->lockForUpdate()->first();

            if (! $p || $p->kgb_mode !== Pegawai::MODE_AUTO) {
                return 0;
            }

            $jumlah = 0;
            $berikutnya = $p->hitungKgbBerikutnya();

            while ($berikutnya && $berikutnya->lte($per)) {
                if (! $dryRun) {
                    $p->kgbHistories()->create([
                        'kgb_lama' => $p->kgb,
                        'kgb_baru' => $berikutnya,
                        'kgb_berikutnya_baru' => Pegawai::tambahInterval($berikutnya),
                        'sumber' => PegawaiKgbHistory::SUMBER_AUTO,
                        'keterangan' => 'Update otomatis oleh sistem',
                    ]);
                }

                $p->kgb = $berikutnya;
                $berikutnya = $p->hitungKgbBerikutnya();
                $jumlah++;
            }

            if (! $dryRun) {
                // Hook saving() di model menghitung ulang kgb_berikutnya & masa kerja.
                $p->save();
            }

            return $jumlah;
        });
    }

    /**
     * Proses semua pegawai (dipakai scheduler & tombol "Jalankan update KGB").
     *
     * @param  callable(Pegawai $pegawai, int $jumlah): void|null  $onDiperbarui
     * @return array{diperiksa:int, pegawai_diperbarui:int, total_kgb:int}
     */
    public function prosesSemua(?CarbonInterface $per = null, bool $dryRun = false, ?callable $onDiperbarui = null): array
    {
        $hasil = ['diperiksa' => 0, 'pegawai_diperbarui' => 0, 'total_kgb' => 0];

        Pegawai::query()->chunkById(200, function ($daftar) use (&$hasil, $per, $dryRun, $onDiperbarui) {
            foreach ($daftar as $pegawai) {
                $hasil['diperiksa']++;

                if ($pegawai->kgb_mode === Pegawai::MODE_AUTO) {
                    $jumlah = $this->prosesOtomatis($pegawai, $per, $dryRun);

                    if ($jumlah > 0) {
                        $hasil['pegawai_diperbarui']++;
                        $hasil['total_kgb'] += $jumlah;

                        if ($onDiperbarui) {
                            $onDiperbarui($pegawai, $jumlah);
                        }
                    }
                } elseif (! $dryRun) {
                    // Mode manual: KGB tidak disentuh, hanya snapshot masa kerja yang disegarkan.
                    $pegawai->save();
                }
            }
        });

        return $hasil;
    }

    /**
     * Update KGB secara manual (mis. SK KGB sudah terbit dengan tanggal berbeda).
     *
     * $kunci = true  -> mode jadi MANUAL; scheduler tidak akan menimpa lagi,
     *                   dan $kgbBerikutnya kustom (bila diisi) dipakai.
     * $kunci = false -> mode tetap AUTO; kgb_berikutnya dihitung ulang (+2 th)
     *                   dan scheduler melanjutkan dari tanggal KGB baru ini.
     */
    public function updateManual(
        Pegawai $pegawai,
        CarbonInterface $kgbBaru,
        ?CarbonInterface $kgbBerikutnya = null,
        ?string $keterangan = null,
        bool $kunci = true,
    ): Pegawai {
        return DB::transaction(function () use ($pegawai, $kgbBaru, $kgbBerikutnya, $keterangan, $kunci) {
            $kgbLama = $pegawai->kgb;

            $pegawai->kgb = $kgbBaru;
            $pegawai->kgb_mode = $kunci ? Pegawai::MODE_MANUAL : Pegawai::MODE_AUTO;

            if ($kunci) {
                $pegawai->kgb_berikutnya = $kgbBerikutnya ?? Pegawai::tambahInterval($kgbBaru);
            }
            // Bila tidak dikunci, hook saving() yang mengisi kgb_berikutnya.

            $pegawai->save();

            $pegawai->kgbHistories()->create([
                'kgb_lama' => $kgbLama,
                'kgb_baru' => $pegawai->kgb,
                'kgb_berikutnya_baru' => $pegawai->kgb_berikutnya,
                'sumber' => PegawaiKgbHistory::SUMBER_MANUAL,
                'keterangan' => $keterangan ?: ($kunci ? 'Update manual (dikunci dari scheduler)' : 'Update manual'),
            ]);

            return $pegawai;
        });
    }

    /**
     * Lepas kunci manual: kembali ke mode AUTO, hitung ulang KGB berikutnya,
     * lalu langsung kejar KGB yang sudah jatuh tempo.
     *
     * @return int jumlah KGB otomatis yang langsung diterapkan
     */
    public function aktifkanOtomatis(Pegawai $pegawai): int
    {
        $pegawai->kgb_mode = Pegawai::MODE_AUTO;
        $pegawai->save();

        return $this->prosesOtomatis($pegawai);
    }
}
