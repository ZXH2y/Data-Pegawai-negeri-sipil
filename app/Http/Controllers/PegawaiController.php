<?php

namespace App\Http\Controllers;

use App\Exports\PegawaiDetailExport;
use App\Exports\PegawaiExport;
use App\Http\Requests\PegawaiRequest;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiController extends Controller
{
    /** Key query string yang dipakai bersama oleh index & export Excel. */
    private const FILTER_KEYS = ['q', 'golongan', 'mode', 'kgb'];

    public function index(Request $request)
    {
        $filter = $request->only(self::FILTER_KEYS);

        $pegawais = $this->queryPegawai($filter)
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        // Angka di tab status KGB: dihitung dengan filter lain yang sedang aktif (tanpa filter KGB).
        $filterTanpaKgb = Arr::except($filter, 'kgb');
        $jumlahStatus = ['semua' => $this->queryPegawai($filterTanpaKgb)->count()];
        foreach (['terlewat', 'bulan_ini', '3_bulan'] as $status) {
            $jumlahStatus[$status] = $this->queryPegawai([...$filterTanpaKgb, 'kgb' => $status])->count();
        }

        $totalPegawai = Pegawai::count();

        return view('pegawai.index', compact('pegawais', 'filter', 'jumlahStatus', 'totalPegawai'));
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

    /** Export daftar pegawai ke Excel (mengikuti filter yang sedang aktif di halaman index). */
    public function exportExcel(Request $request)
    {
        $filter = $request->only(self::FILTER_KEYS);

        $pegawais = $this->queryPegawai($filter)->orderBy('nama')->get();

        $namaFile = 'data-pegawai-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new PegawaiExport($pegawais, $filter, now()), $namaFile);
    }

    /** Export detail satu pegawai beserta riwayat KGB-nya ke Excel. */
    public function exportDetailExcel(Pegawai $pegawai)
    {
        $pegawai->load('kgbHistories');

        return Excel::download(
            new PegawaiDetailExport($pegawai, now()),
            'pegawai-'.$pegawai->nip.'.xlsx'
        );
    }

    /**
     * Query pegawai sesuai filter.
     *
     * Filter golongan ditangani di sini: kolom gol_pangkat_terakhir berisi pangkat + golongan
     * (mis. "Pembina IV/a" atau "Pengatur Muda - II/a"), sedangkan dropdown mengirim kodenya
     * saja ("IV/a"), jadi pencocokan persis (=) tidak pernah ketemu. Di sini dicocokkan dengan
     * akhiran " IV/a". Spasi di depannya penting supaya "II/a" tidak ikut cocok ke "III/a".
     */
    private function queryPegawai(array $filter): Builder
    {
        $golongan = trim((string) ($filter['golongan'] ?? ''));

        return Pegawai::query()
            ->filter([...$filter, 'golongan' => null]) // filter lain tetap lewat scope bawaan
            ->when($golongan !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('gol_pangkat_terakhir', $golongan)
                ->orWhere('gol_pangkat_terakhir', 'like', '% '.$golongan)));
    }
}