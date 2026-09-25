<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pegawai extends Model
{
    public const MODE_AUTO = 'auto';
    public const MODE_MANUAL = 'manual';

    /** Interval KGB dalam tahun (aturan bisnis: +2 tahun). */
    public const INTERVAL_KGB_TAHUN = 2;

    /**
     * Daftar golongan/ruang PNS. Yang disimpan ke DB hanya KODE-nya (mis. "III/a")
     * karena kolom gol_pangkat_* hanya varchar(20).
     */
    public const GOLONGAN = [
        'I/a'   => 'Juru Muda',
        'I/b'   => 'Juru Muda Tingkat I',
        'I/c'   => 'Juru',
        'I/d'   => 'Juru Tingkat I',
        'II/a'  => 'Pengatur Muda',
        'II/b'  => 'Pengatur Muda Tingkat I',
        'II/c'  => 'Pengatur',
        'II/d'  => 'Pengatur Tingkat I',
        'III/a' => 'Penata Muda',
        'III/b' => 'Penata Muda Tingkat I',
        'III/c' => 'Penata',
        'III/d' => 'Penata Tingkat I',
        'IV/a'  => 'Pembina',
        'IV/b'  => 'Pembina Tingkat I',
        'IV/c'  => 'Pembina Utama Muda',
        'IV/d'  => 'Pembina Utama Madya',
        'IV/e'  => 'Pembina Utama',
    ];

    protected $table = 'pegawais';

    protected $fillable = [
        'nip',
        'nama',
        'gol_pangkat_cpns',
        'jabatan',
        'gol_pangkat_terakhir',
        'tmt_pangkat_terakhir',
        'tmt_cpns',
        'kgb',
        'masa_kerja_tahun',
        'masa_kerja_bulan',
        // kgb_berikutnya & kgb_mode sengaja TIDAK fillable:
        // keduanya hanya diubah lewat KgbService / hook saving di bawah.
    ];

    /** Default di sisi PHP supaya hook saving() tahu mode sebelum record pertama kali disimpan. */
    protected $attributes = [
        'kgb_mode' => self::MODE_AUTO,
        'masa_kerja_tahun' => 0,
        'masa_kerja_bulan' => 0,
    ];

    protected function casts(): array
    {
        return [
            'tmt_pangkat_terakhir' => 'date',
            'tmt_cpns' => 'date',
            'kgb' => 'date',
            'kgb_berikutnya' => 'date',
            'masa_kerja_tahun' => 'integer',
            'masa_kerja_bulan' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Pegawai $pegawai) {
            // 1) Mode auto: kgb_berikutnya SELALU diturunkan dari aturan bisnis.
            //    Mode manual: dibiarkan apa adanya (itulah fungsi penanda kgb_mode).
            if ($pegawai->kgb_mode === self::MODE_AUTO) {
                $pegawai->kgb_berikutnya = $pegawai->hitungKgbBerikutnya();
            }

            // 2) Sinkronkan snapshot masa kerja ke kolom DB bila tmt_cpns diisi.
            if ($pegawai->tmt_cpns) {
                $mk = $pegawai->hitungMasaKerja();
                $pegawai->masa_kerja_tahun = $mk['tahun'];
                $pegawai->masa_kerja_bulan = $mk['bulan'];
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Relasi                                                             */
    /* ------------------------------------------------------------------ */

    public function kgbHistories(): HasMany
    {
        return $this->hasMany(PegawaiKgbHistory::class)->latest('id');
    }

    public function kgbHistoryTerakhir(): HasOne
    {
        return $this->hasOne(PegawaiKgbHistory::class)->latestOfMany();
    }

    /* ------------------------------------------------------------------ */
    /*  Aturan bisnis KGB                                                  */
    /* ------------------------------------------------------------------ */

    /** Tambah interval KGB. NoOverflow: 29 Feb + 2 th = 28 Feb, bukan 1 Mar. */
    public static function tambahInterval(CarbonInterface $tanggal): Carbon
    {
        return Carbon::instance($tanggal)->copy()->addYearsNoOverflow(self::INTERVAL_KGB_TAHUN);
    }

    /** KGB berikutnya = KGB terakhir + 2 th; bila belum pernah KGB = TMT pangkat terakhir + 2 th. */
    public function hitungKgbBerikutnya(): ?Carbon
    {
        $dasar = $this->kgb ?? $this->tmt_pangkat_terakhir;

        return $dasar ? self::tambahInterval($dasar) : null;
    }

    /* ------------------------------------------------------------------ */
    /*  Masa kerja                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Masa kerja per tanggal tertentu (default hari ini), dihitung dari tmt_cpns.
     * Bila tmt_cpns kosong, pakai nilai yang tersimpan di kolom (input manual).
     *
     * @return array{tahun:int, bulan:int}
     */
    public function hitungMasaKerja(?CarbonInterface $per = null): array
    {
        if (! $this->tmt_cpns) {
            return [
                'tahun' => (int) $this->masa_kerja_tahun,
                'bulan' => (int) $this->masa_kerja_bulan,
            ];
        }

        $per = Carbon::instance($per ?? now())->startOfDay();

        if ($this->tmt_cpns->greaterThan($per)) {
            return ['tahun' => 0, 'bulan' => 0];
        }

        // diff() -> DateInterval: aman di Carbon 2 maupun 3
        // (di Carbon 3, diffInYears() mengembalikan float).
        $selisih = $this->tmt_cpns->diff($per);

        return ['tahun' => $selisih->y, 'bulan' => $selisih->m];
    }

    /** $pegawai->masa_kerja  => ['tahun' => 12, 'bulan' => 3] (live, per hari ini) */
    protected function masaKerja(): Attribute
    {
        return Attribute::get(fn () => $this->hitungMasaKerja());
    }

    /** $pegawai->masa_kerja_label  => "12 tahun 3 bulan" */
    protected function masaKerjaLabel(): Attribute
    {
        return Attribute::get(function () {
            $mk = $this->hitungMasaKerja();

            return "{$mk['tahun']} tahun {$mk['bulan']} bulan";
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Accessor tampilan                                                  */
    /* ------------------------------------------------------------------ */

    /** "III/a - Penata Muda" */
    public static function labelGolongan(?string $kode): string
    {
        if (! $kode) {
            return '-';
        }

        return isset(self::GOLONGAN[$kode]) ? $kode.' - '.self::GOLONGAN[$kode] : $kode;
    }

    protected function golPangkatCpnsLabel(): Attribute
    {
        return Attribute::get(fn () => self::labelGolongan($this->gol_pangkat_cpns));
    }

    protected function golPangkatTerakhirLabel(): Attribute
    {
        return Attribute::get(fn () => self::labelGolongan($this->gol_pangkat_terakhir));
    }

    /**
     * Status KGB untuk badge: terlewat | bulan_ini | segera (≤ 3 bulan) | normal | null.
     */
    protected function statusKgb(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->kgb_berikutnya) {
                return null;
            }

            $hariIni = now()->startOfDay();

            if ($this->kgb_berikutnya->lt($hariIni->copy()->startOfMonth())) {
                return 'terlewat';
            }
            if ($this->kgb_berikutnya->lte($hariIni->copy()->endOfMonth())) {
                return 'bulan_ini';
            }
            if ($this->kgb_berikutnya->lte($hariIni->copy()->addMonthsNoOverflow(3)->endOfMonth())) {
                return 'segera';
            }

            return 'normal';
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Scope untuk pencarian/filter (dipakai index & export PDF)          */
    /* ------------------------------------------------------------------ */

    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['q'] ?? null, function (Builder $q, string $cari) {
                $q->where(fn (Builder $w) => $w
                    ->where('nip', 'like', "%{$cari}%")
                    ->orWhere('nama', 'like', "%{$cari}%")
                    ->orWhere('jabatan', 'like', "%{$cari}%"));
            })
            ->when($filter['golongan'] ?? null, fn (Builder $q, string $gol) => $q->where('gol_pangkat_terakhir', $gol))
            ->when(in_array($filter['mode'] ?? null, [self::MODE_AUTO, self::MODE_MANUAL], true),
                fn (Builder $q) => $q->where('kgb_mode', $filter['mode']))
            ->when($filter['kgb'] ?? null, function (Builder $q, string $status) {
                $hariIni = now()->startOfDay();

                match ($status) { 
                    'bulan_ini' => $q->whereBetween('kgb_berikutnya', [
                        $hariIni->copy()->startOfMonth()->toDateString(),
                        $hariIni->copy()->endOfMonth()->toDateString(),
                    ]),
                    '3_bulan' => $q->whereBetween('kgb_berikutnya', [
                        $hariIni->copy()->startOfMonth()->toDateString(),
                        $hariIni->copy()->addMonthsNoOverflow(3)->endOfMonth()->toDateString(),
                    ]),
                    'terlewat' => $q->where('kgb_berikutnya', '<', $hariIni->copy()->startOfMonth()->toDateString()),
                    default => null,
                };
            });
    }
}
