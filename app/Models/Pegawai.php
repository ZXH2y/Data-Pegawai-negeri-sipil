<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
        public const MODE_AUTO = 'auto';   
        public const MODE_MANUAL = 'manual'; 
        
        public const INTERVAL_KGB_TAHUN = 2;


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
    ];

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
            'masa_kerja_bulan' => 'interge',
        ];
    }
    protected static function booted(): void 
    {
        static::saving(function (Pegawai $pegawai){
            if($pegawai->kgb_mode === self::MODE_AUTO){
                $pegawai->kgb_berikutnya = $pegawai->hitungKgbBerikutnya();
            }
            if($pegawai->tmt_cpns){
                $mk = $pegawai->hitungMasaKerja();
                $pegawai->masa_kerja_tahun = $mk['tahun'];
                $pegawai->masa_kerja_bulan = $mk['bulan'];
                
            }
        });
    }

    public function kgbHistories(): HasMany
    {
        return $this->hasMany(PegawaiKgbHistory::class)->lates('id');
    }
    public function kgbHistoryTerakhir(): HasOne
    {
        return $this->hasOne(PegawaiKgbHistory::class)->latestOfMany();
    }

    public static function tambahInterval(CarbonInterface $tanggal): Carbon
    {
        return Carbon::instance($tanggal)->copy()->addYearsNoOverflow(self::INTERVAL_KGB_TAHUN);
    }

    public function hitungKgbBerikutnya(): ?Carbon
    {
        $dasar = $this->kgb ?? $this->tmt_pangkat_terakhir;

        return $dasar ? self::tambahInterval($dasar) : null;
    }

    public function hitungMasaKerja(?CarbonInterface $per = null): array
    {
            if(! $this->tmt_cpns){
                return [
                'tahun' => (int) $this->masa_Kerja_tahun,
                'bulan' => (int) $this->masa_kerja_bulan,
            ];
        }
        $per = Carbon::instance($per ?? now())->startDay();
        
        if($this->tmt_cpns->greaterThan($per)){
            return ['tahun' => 0, 'bulan' => 0];
        }

        $selisih = $this->tmt_cpns->diff($per);

        return ['tahun' => $selisih->y, 'bulan' => $selisih->m];
    }

    protected function masaKerja(): Attribute
    {
        return Attribute::get(fn () => $this->hitungMasaKerja());
    }

    protected function masaKerjaLabel(): Attribute
    {
        return Attribute::get(function () {
            $mk = $this->hitungMasaKerja();

            return "{$mk['tahun']} tahun {$mk['bulan']} bulan";
        });
    }

    public static function labelGolongan(? string $kode): string
    {
        if(! $kode){
            return '-';
        }
        return isset(self::GOLONGAN[$kode]) ? $kode.' - '.self::GOLONGAN[$kode] : $kode;
    }
    protected function golPangkatTerakhirLabel(): Attribute
    {
        return Attribute::get(fn () => self::labelGolongan($this->gol_pangkat_cpns))
    }
}
