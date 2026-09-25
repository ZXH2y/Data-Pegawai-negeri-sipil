<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PegawaiKgbHistory extends Model
{
    public const SUMBER_AUTO = 'auto';
    public const SUMBER_MANUAL = 'manual';

    protected $table = 'pegawai_kgb_histories';

    protected $fillable = [
        'pegawai_id',
        'kgb_lama',
        'kgb_baru',
        'kgb_berikutnya_baru',
        'sumber',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'kgb_lama' => 'date',
            'kgb_baru' => 'date',
            'kgb_berikutnya_baru' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
