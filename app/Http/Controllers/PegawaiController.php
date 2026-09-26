<?php

namespace App\Http\Controllers;

use App\Http\Requests\PegawaiRequest;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    /** Key query string yang dipakai bersama oleh index & export PDF. */
    private const FILTER_KEYS = ['q', 'golongan', 'mode', 'kgb'];

    public function index(Request $request)
    {
        $filter = $request->only(self::FILTER_KEYS);

        $pegawais = Pegawai::query()
            ->filter($filter)
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('pegawai.index', compact('pegawais', 'filter'));
    }

    public function create()
    {
        return view('pegawai.create', ['pegawai' => new Pegawai()]);
    }

    public function store(PegawaiRequest $request)
    {
        $pegawai = Pegawai::create($request->validated());

        return redirect()
            ->route('pegawai.show', $pegawai)
            ->with('success', "Data pegawai {$pegawai->nama} berhasil ditambahkan.");
    }

    public function show(Pegawai $pegawai)
    {
        $pegawai->load('kgbHistories');

        return view('pegawai.show', compact('pegawai'));
    }

    public function edit(Pegawai $pegawai)
    {
        return view('pegawai.edit', compact('pegawai'));
    }

    public function update(PegawaiRequest $request, Pegawai $pegawai)
    {
        // 'kgb' tidak ada di validated() saat update (lihat PegawaiRequest).
        $pegawai->update($request->validated());

        return redirect()
            ->route('pegawai.show', $pegawai)
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Pegawai $pegawai)
    {
        $nama = $pegawai->nama;
        $pegawai->delete(); // riwayat KGB ikut terhapus (cascadeOnDelete)

        return redirect()
            ->route('pegawai.index')
            ->with('success', "Data pegawai {$nama} berhasil dihapus.");
    }

    /** Export daftar pegawai (mengikuti filter yang sedang aktif di halaman index). */
    public function exportPdf(Request $request)
    {
        $filter = $request->only(self::FILTER_KEYS);

        $pegawais = Pegawai::query()->filter($filter)->orderBy('nama')->get();

        $pdf = Pdf::loadView('pegawai.pdf.daftar', [
            'pegawais' => $pegawais,
            'filter' => $filter,
            'dicetak' => now(),
        ])->setPaper('a4', 'landscape');

        $namaFile = 'data-pegawai-'.now()->format('Ymd-His').'.pdf';

        // ?preview=1 -> tampil di tab browser; selain itu langsung unduh.
        return $request->boolean('preview') ? $pdf->stream($namaFile) : $pdf->download($namaFile);
    }

    /** Export detail satu pegawai beserta riwayat KGB-nya. */
    public function exportDetailPdf(Pegawai $pegawai)
    {
        $pegawai->load('kgbHistories');

        return Pdf::loadView('pegawai.pdf.detail', [
            'pegawai' => $pegawai,
            'dicetak' => now(),
        ])->setPaper('a4', 'portrait')
            ->stream('pegawai-'.$pegawai->nip.'.pdf');
    }
}
