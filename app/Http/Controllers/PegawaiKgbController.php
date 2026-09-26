<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Services\KgbService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PegawaiKgbController extends Controller
{
    public function __construct(private readonly KgbService $kgb)
    {
    }

    /** Update KGB manual untuk satu pegawai (form di halaman detail). */
    public function store(Request $request, Pegawai $pegawai)
    {
        $data = $request->validate([
            'kgb_baru' => ['required', 'date'],
            'kgb_berikutnya' => ['nullable', 'date', 'after:kgb_baru'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'kunci_manual' => ['nullable', 'boolean'],
        ], [], [
            'kgb_baru' => 'tanggal KGB baru',
            'kgb_berikutnya' => 'tanggal KGB berikutnya',
        ]);

        $this->kgb->updateManual(
            $pegawai,
            Carbon::parse($data['kgb_baru']),
            ! empty($data['kgb_berikutnya']) ? Carbon::parse($data['kgb_berikutnya']) : null,
            $data['keterangan'] ?? null,
            $request->boolean('kunci_manual'),
        );

        return redirect()
            ->route('pegawai.show', $pegawai)
            ->with('success', 'KGB berhasil diperbarui secara manual.');
    }

    /** Lepas kunci manual -> kembali ke mode otomatis. */
    public function aktifkanOtomatis(Pegawai $pegawai)
    {
        $jumlah = $this->kgb->aktifkanOtomatis($pegawai);

        $pesan = 'Mode KGB dikembalikan ke otomatis.';
        if ($jumlah > 0) {
            $pesan .= " {$jumlah} KGB yang sudah jatuh tempo langsung diterapkan.";
        }

        return redirect()->route('pegawai.show', $pegawai)->with('success', $pesan);
    }

    /** Tombol "Jalankan update KGB sekarang" = menjalankan logika scheduler saat itu juga. */
    public function prosesSemua()
    {
        $hasil = $this->kgb->prosesSemua();

        return back()->with('success', sprintf(
            'Update KGB selesai: %d pegawai diperiksa, %d pegawai diperbarui (%d KGB).',
            $hasil['diperiksa'],
            $hasil['pegawai_diperbarui'],
            $hasil['total_kgb'],
        ));
    }
}
