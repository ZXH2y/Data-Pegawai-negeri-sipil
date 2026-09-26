<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PegawaiSeeder extends Seeder
{
    /**
     * Kolom yang boleh null. Nilai '-' atau kosong di kolom ini
     * otomatis diubah menjadi null sebelum insert.
     */
    private array $nullableColumns = [
        'gol_pangkat_cpns',
        'tmt_pangkat_terakhir',
        'tmt_cpns',
        'kgb',
        'kgb_berikutnya',
    ];

    public function run(): void
    {
        $data = [
            ['nip' => '197404102000031007', 'nama' => 'NOLDY YOSEPH RENGKUAN, SE., M.Si', 'gol_pangkat_cpns' => null, 'jabatan' => 'Plt. Kadis Kominfo', 'gol_pangkat_terakhir' => 'Pembina Tk I IV/b', 'tmt_pangkat_terakhir' => null, 'tmt_cpns' => null, 'kgb' => null, 'kgb_berikutnya' => null, 'masa_kerja_tahun' => 0, 'masa_kerja_bulan' => 0],
            ['nip' => '197610032006041007', 'nama' => 'MUH YUSNI, S.IP., M.Si', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Kepala Bidang Persandian dan Statistik', 'gol_pangkat_terakhir' => 'Pembina IV/a', 'tmt_pangkat_terakhir' => '2022-04-01', 'tmt_cpns' => '2006-04-01', 'kgb' => '2026-04-01', 'kgb_berikutnya' => '2028-04-01', 'masa_kerja_tahun' => 20, 'masa_kerja_bulan' => 5],
            ['nip' => '198310282010011032', 'nama' => 'EKANUANSA ASKAR HABIB AS, SE., MM', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Kabid Penyelenggaraan E-government', 'gol_pangkat_terakhir' => 'Pembina IV/a', 'tmt_pangkat_terakhir' => '2025-04-01', 'tmt_cpns' => '2010-01-01', 'kgb' => '2026-01-01', 'kgb_berikutnya' => '2028-01-01', 'masa_kerja_tahun' => 16, 'masa_kerja_bulan' => 8],
            ['nip' => '197611232010011003', 'nama' => 'BUDIANSYAH NADJPA, SE', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Kepala Bidang Humas dan Komunikasi Publik', 'gol_pangkat_terakhir' => 'Penata Tk I - III/d', 'tmt_pangkat_terakhir' => '2022-04-01', 'tmt_cpns' => '2010-01-01', 'kgb' => '2025-01-01', 'kgb_berikutnya' => '2027-01-01', 'masa_kerja_tahun' => 21, 'masa_kerja_bulan' => 8],
            ['nip' => '197601152005021002', 'nama' => 'ANDI IKBAL, S.Pt., MM', 'gol_pangkat_cpns' => 'Pengatur Muda - II/a', 'jabatan' => 'Kasubag Administrasi Umum dan Kepegawaian', 'gol_pangkat_terakhir' => 'Pembina IV/a', 'tmt_pangkat_terakhir' => '2026-04-01', 'tmt_cpns' => '2005-02-01', 'kgb' => '2026-02-01', 'kgb_berikutnya' => '2028-02-01', 'masa_kerja_tahun' => 16, 'masa_kerja_bulan' => 7],
            ['nip' => '198011232006041014', 'nama' => 'ALIMUDDIN, SE., MM', 'gol_pangkat_cpns' => 'Juru - I/c', 'jabatan' => 'Kasubag Program dan Keuangan', 'gol_pangkat_terakhir' => 'Penata - III/c', 'tmt_pangkat_terakhir' => '2024-04-01', 'tmt_cpns' => null, 'kgb' => '2026-04-01', 'kgb_berikutnya' => '2028-04-01', 'masa_kerja_tahun' => 12, 'masa_kerja_bulan' => 5],
            ['nip' => '197705021995111001', 'nama' => 'HARIYANTO CHAIRUN, S.STP', 'gol_pangkat_cpns' => 'Pengatur Muda II/a', 'jabatan' => '-', 'gol_pangkat_terakhir' => 'Pembina Tk I IV/b', 'tmt_pangkat_terakhir' => '2020-04-01', 'tmt_cpns' => '1995-11-01', 'kgb' => '2025-10-01', 'kgb_berikutnya' => '2027-10-01', 'masa_kerja_tahun' => 26, 'masa_kerja_bulan' => 11],
            ['nip' => '197707222006042005', 'nama' => 'NILVIANI, S.Sos., M.Si', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Statistisi Ahli Muda', 'gol_pangkat_terakhir' => 'Pembina IV/a', 'tmt_pangkat_terakhir' => '2022-04-01', 'tmt_cpns' => '2006-04-01', 'kgb' => '2026-04-01', 'kgb_berikutnya' => '2028-04-01', 'masa_kerja_tahun' => 20, 'masa_kerja_bulan' => 5],
            ['nip' => '197611252006041001', 'nama' => 'JOKO SETIOKO, ST', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Pranata Komputer Ahli Muda', 'gol_pangkat_terakhir' => 'Penata Tk I - III/d', 'tmt_pangkat_terakhir' => '2016-04-01', 'tmt_cpns' => '2006-04-01', 'kgb' => '2026-04-01', 'kgb_berikutnya' => '2028-04-01', 'masa_kerja_tahun' => 20, 'masa_kerja_bulan' => 5],
            ['nip' => '199106042020121004', 'nama' => 'MULYADI, S.H.', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Penelaah Teknis Kebijakan', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2024-12-01', 'tmt_cpns' => '2020-12-01', 'kgb' => '2022-12-01', 'kgb_berikutnya' => '2026-12-01', 'masa_kerja_tahun' => 5, 'masa_kerja_bulan' => 9],
            ['nip' => '197404112008011005', 'nama' => 'MUH NUR IBRAHIM, SE', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Pengolah Data dan Informasi', 'gol_pangkat_terakhir' => 'Penata Tk I - III/d', 'tmt_pangkat_terakhir' => '2020-04-01', 'tmt_cpns' => '2008-01-01', 'kgb' => '2025-09-01', 'kgb_berikutnya' => '2027-09-01', 'masa_kerja_tahun' => 23, 'masa_kerja_bulan' => 0],
            ['nip' => '198509112015041001', 'nama' => 'SHUFI SUMARDA, ST', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Pranata Komputer Ahli Muda', 'gol_pangkat_terakhir' => 'Penata - III/c', 'tmt_pangkat_terakhir' => '2022-10-01', 'tmt_cpns' => '2015-04-01', 'kgb' => '2025-04-01', 'kgb_berikutnya' => '2027-04-01', 'masa_kerja_tahun' => 11, 'masa_kerja_bulan' => 5],
            ['nip' => '199402062015072001', 'nama' => 'SUCI FEBRIANTI, S.STP, MM', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Pranata Hubungan Masyarakat Ahli Muda', 'gol_pangkat_terakhir' => 'Penata - III/c', 'tmt_pangkat_terakhir' => '2022-10-01', 'tmt_cpns' => '2015-07-01', 'kgb' => '2025-07-01', 'kgb_berikutnya' => '2027-07-01', 'masa_kerja_tahun' => 11, 'masa_kerja_bulan' => 2],
            ['nip' => '197205122005021001', 'nama' => 'MOCHTAR, SH', 'gol_pangkat_cpns' => null, 'jabatan' => 'Penelaah Teknis Kebijakan', 'gol_pangkat_terakhir' => 'Penata - III/c', 'tmt_pangkat_terakhir' => '2024-04-01', 'tmt_cpns' => null, 'kgb' => '2025-02-01', 'kgb_berikutnya' => '2027-02-01', 'masa_kerja_tahun' => 13, 'masa_kerja_bulan' => 7],
            ['nip' => '197908012009022005', 'nama' => 'AGUSTINA DAHLAN, A.Md', 'gol_pangkat_cpns' => 'Pengatur - II/c', 'jabatan' => 'Penelaah Teknis Kebijakan', 'gol_pangkat_terakhir' => 'Penata - III/c', 'tmt_pangkat_terakhir' => '2025-10-01', 'tmt_cpns' => '2009-02-01', 'kgb' => '2025-02-01', 'kgb_berikutnya' => '2027-02-01', 'masa_kerja_tahun' => 15, 'masa_kerja_bulan' => 7],
            ['nip' => '197012311993081007', 'nama' => 'SUPARDI', 'gol_pangkat_cpns' => 'Juru Muda - I/a', 'jabatan' => 'Pengolah Data dan Informasi', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2022-10-01', 'tmt_cpns' => '1993-08-01', 'kgb' => '2022-08-01', 'kgb_berikutnya' => '2024-08-01', 'masa_kerja_tahun' => 26, 'masa_kerja_bulan' => 1],
            ['nip' => '199502092019031004', 'nama' => 'FACHRIANSYAH, S.ST', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Penata Kelola Sistem dan Teknologi Informasi', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2023-04-01', 'tmt_cpns' => '2019-03-01', 'kgb' => '2025-03-01', 'kgb_berikutnya' => '2027-03-01', 'masa_kerja_tahun' => 7, 'masa_kerja_bulan' => 6],
            ['nip' => '199606222019032003', 'nama' => 'RAZANAH AMIN UMAR, S.ST', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Statistisi Ahli Pertama', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2023-10-01', 'tmt_cpns' => '2019-03-01', 'kgb' => '2025-03-01', 'kgb_berikutnya' => '2027-03-01', 'masa_kerja_tahun' => 7, 'masa_kerja_bulan' => 6],
            ['nip' => '198408232005021002', 'nama' => 'MUKMIN, SH', 'gol_pangkat_cpns' => 'Pengatur Muda - II/a', 'jabatan' => 'Penelaah Teknis Kebijakan', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2023-04-01', 'tmt_cpns' => '2005-02-01', 'kgb' => '2026-02-01', 'kgb_berikutnya' => '2028-02-01', 'masa_kerja_tahun' => 16, 'masa_kerja_bulan' => 7],
            ['nip' => '199610302020122000', 'nama' => 'ANDI AYU SRIWAHYUNI, S.Stat', 'gol_pangkat_cpns' => 'Penata Muda - III/a', 'jabatan' => 'Penelaah Teknis Kebijakan', 'gol_pangkat_terakhir' => 'Penata Muda Tk I - III/b', 'tmt_pangkat_terakhir' => '2024-12-01', 'tmt_cpns' => '2020-12-01', 'kgb' => '2022-12-01', 'kgb_berikutnya' => '2026-12-01', 'masa_kerja_tahun' => 5, 'masa_kerja_bulan' => 9],
        ];

        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                foreach ($this->nullableColumns as $col) {
                    if (isset($row[$col]) && in_array(trim((string) $row[$col]), ['-', ''], true)) {
                        $row[$col] = null;
                    }
                }

                DB::table('pegawais')->updateOrInsert(
                    ['nip' => $row['nip']],
                    array_merge($row, [
                        'kgb_mode'   => 'auto',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        });
    }
}