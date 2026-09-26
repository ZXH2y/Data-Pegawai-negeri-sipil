<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Pegawai</title>
    {{-- dompdf TIDAK mendukung Tailwind/flexbox/grid: gunakan CSS sederhana + tabel. --}}
    <style>
        @page { margin: 90px 30px 50px 30px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #111; }
        header { position: fixed; top: -70px; left: 0; right: 0; text-align: center; }
        header h1 { font-size: 14px; margin: 0; }
        header p { margin: 2px 0 0; font-size: 9px; color: #444; }
        footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 8px; color: #666; }
        footer .kanan { float: right; }
        footer .halaman:after { content: counter(page); }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 4px 5px; vertical-align: top; }
        th { background: #e5e7eb; font-weight: bold; text-align: center; }
        tr { page-break-inside: avoid; }
        .tengah { text-align: center; }
        .nowrap { white-space: nowrap; }
        .kecil { font-size: 8px; color: #555; }
        .merah { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
<header>
    <h1>DAFTAR PEGAWAI NEGERI SIPIL</h1>
    <p>
        Keadaan per {{ $dicetak->translatedFormat('d F Y') }}
        @if (!empty($filter['golongan'])) &middot; Golongan {{ $filter['golongan'] }} @endif
        @if (($filter['kgb'] ?? '') === 'bulan_ini') &middot; KGB jatuh tempo bulan ini @endif
        @if (($filter['kgb'] ?? '') === '3_bulan') &middot; KGB jatuh tempo ≤ 3 bulan @endif
        @if (($filter['kgb'] ?? '') === 'terlewat') &middot; KGB terlewat @endif
        @if (!empty($filter['q'])) &middot; Pencarian: "{{ $filter['q'] }}" @endif
    </p>
</header>

<footer>
    Dicetak {{ $dicetak->format('d-m-Y H:i') }}
    <span class="kanan">Halaman <span class="halaman"></span></span>
</footer>

<table>
    <thead>
        <tr>
            <th style="width: 3%">No</th>
            <th style="width: 20%">Nama / NIP</th>
            <th style="width: 7%">Gol. CPNS</th>
            <th style="width: 12%">Gol. Terakhir / TMT</th>
            <th>Jabatan</th>
            <th style="width: 9%">TMT CPNS</th>
            <th style="width: 9%">Masa Kerja</th>
            <th style="width: 8%">KGB Terakhir</th>
            <th style="width: 8%">KGB Berikutnya</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pegawais as $i => $p)
            <tr>
                <td class="tengah">{{ $i + 1 }}</td>
                <td>
                    {{ $p->nama }}<br>
                    <span class="kecil">NIP {{ $p->nip }}</span>
                </td>
                <td class="tengah">{{ $p->gol_pangkat_cpns }}</td>
                <td>
                    {{ $p->gol_pangkat_terakhir_label }}<br>
                    <span class="kecil">TMT {{ $p->tmt_pangkat_terakhir?->format('d-m-Y') }}</span>
                </td>
                <td>{{ $p->jabatan }}</td>
                <td class="tengah nowrap">{{ $p->tmt_cpns?->format('d-m-Y') ?? '-' }}</td>
                <td class="tengah">{{ $p->masa_kerja['tahun'] }} th {{ $p->masa_kerja['bulan'] }} bl</td>
                <td class="tengah nowrap">{{ $p->kgb?->format('d-m-Y') ?? '-' }}</td>
                <td class="tengah nowrap {{ in_array($p->status_kgb, ['terlewat', 'bulan_ini']) ? 'merah' : '' }}">
                    {{ $p->kgb_berikutnya?->format('d-m-Y') ?? '-' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="tengah">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
