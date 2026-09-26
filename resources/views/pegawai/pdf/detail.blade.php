<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Pegawai - {{ $pegawai->nip }}</title>
    <style>
        @page { margin: 40px 45px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 14px; text-align: center; margin: 0 0 16px; }
        h2 { font-size: 11px; margin: 18px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        table.data td { padding: 3px 4px; vertical-align: top; }
        table.data td.label { width: 32%; }
        table.data td.titik { width: 2%; }
        table.riwayat { width: 100%; border-collapse: collapse; }
        table.riwayat th, table.riwayat td { border: 1px solid #555; padding: 4px; }
        table.riwayat th { background: #e5e7eb; }
        .tengah { text-align: center; }
        .kecil { font-size: 8px; color: #666; }
    </style>
</head>
<body>
@php $tgl = fn ($d) => $d?->translatedFormat('d F Y') ?? '-'; @endphp

<h1>DATA PEGAWAI NEGERI SIPIL</h1>

<h2>A. Data Kepegawaian</h2>
<table class="data">
    <tr><td class="label">Nama</td><td class="titik">:</td><td>{{ $pegawai->nama }}</td></tr>
    <tr><td class="label">NIP</td><td class="titik">:</td><td>{{ $pegawai->nip }}</td></tr>
    <tr><td class="label">Jabatan</td><td class="titik">:</td><td>{{ $pegawai->jabatan }}</td></tr>
    <tr><td class="label">Gol/Pangkat CPNS</td><td class="titik">:</td><td>{{ $pegawai->gol_pangkat_cpns_label }}</td></tr>
    <tr><td class="label">Gol/Pangkat terakhir</td><td class="titik">:</td><td>{{ $pegawai->gol_pangkat_terakhir_label }}</td></tr>
    <tr><td class="label">TMT pangkat terakhir</td><td class="titik">:</td><td>{{ $tgl($pegawai->tmt_pangkat_terakhir) }}</td></tr>
    <tr><td class="label">TMT CPNS</td><td class="titik">:</td><td>{{ $tgl($pegawai->tmt_cpns) }}</td></tr>
    <tr><td class="label">Masa kerja</td><td class="titik">:</td><td>{{ $pegawai->masa_kerja_label }}</td></tr>
</table>

<h2>B. Kenaikan Gaji Berkala</h2>
<table class="data">
    <tr><td class="label">KGB terakhir</td><td class="titik">:</td><td>{{ $tgl($pegawai->kgb) }}</td></tr>
    <tr><td class="label">KGB berikutnya</td><td class="titik">:</td><td>{{ $tgl($pegawai->kgb_berikutnya) }}</td></tr>
    <tr><td class="label">Mode pembaruan</td><td class="titik">:</td><td>{{ $pegawai->kgb_mode === 'manual' ? 'Manual' : 'Otomatis' }}</td></tr>
</table>

<h2>C. Riwayat Perubahan KGB</h2>
<table class="riwayat">
    <thead>
        <tr>
            <th>No</th><th>KGB Lama</th><th>KGB Baru</th><th>KGB Berikutnya</th><th>Sumber</th><th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pegawai->kgbHistories as $i => $h)
            <tr>
                <td class="tengah">{{ $i + 1 }}</td>
                <td class="tengah">{{ $h->kgb_lama?->format('d-m-Y') ?? '-' }}</td>
                <td class="tengah">{{ $h->kgb_baru->format('d-m-Y') }}</td>
                <td class="tengah">{{ $h->kgb_berikutnya_baru->format('d-m-Y') }}</td>
                <td class="tengah">{{ ucfirst($h->sumber) }}</td>
                <td>{{ $h->keterangan ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="tengah">Belum ada riwayat.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="kecil" style="margin-top: 24px;">Dicetak {{ $dicetak->format('d-m-Y H:i') }}</p>
</body>
</html>
