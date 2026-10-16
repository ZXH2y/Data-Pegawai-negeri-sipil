@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
@php
    // ---------- Filter aktif (nilai kosong dibuang supaya URL bersih) ----------
    $aktif = array_filter($filter, fn ($v) => filled($v));
    $kgbAktif = $aktif['kgb'] ?? '';
    $urlIndex = fn (array $params) => route('pegawai.index', array_filter($params, fn ($v) => filled($v)));

    $jumlahStatus = $jumlahStatus ?? null;
    $totalPegawai = $totalPegawai ?? $pegawais->total();

    $tabs = [
        '' => 'Semua',
        'terlewat' => 'Terlewat',
        'bulan_ini' => 'Jatuh tempo bulan ini',
        '3_bulan' => '≤ 3 bulan',
    ];

    $chips = [];
    if (filled($aktif['q'] ?? null)) $chips['q'] = 'Cari: “'.$aktif['q'].'”';
    if (filled($aktif['golongan'] ?? null)) $chips['golongan'] = 'Golongan '.$aktif['golongan'];
    if (filled($aktif['mode'] ?? null)) $chips['mode'] = $aktif['mode'] === 'manual' ? 'Mode manual' : 'Mode otomatis';

    // ---------- Helper tampilan ----------
    // NIP dipecah 8-6-1-3 dengan jarak CSS, jadi saat disalin tetap 18 digit tanpa spasi.
    $nip = function ($nip) {
        $nip = (string) $nip;
        if (strlen($nip) !== 18) return e($nip);
        $bagian = [substr($nip, 0, 8), substr($nip, 8, 6), substr($nip, 14, 1), substr($nip, 15, 3)];
        $html = '';
        foreach ($bagian as $i => $b) $html .= '<span'.($i ? ' class="ml-[0.4em]"' : '').'>'.e($b).'</span>';
        return $html;
    };

    $tgl = fn ($d) => $d ? $d->translatedFormat('d M Y') : '–';

    $sisaHari = fn ($d) => $d ? (int) now()->startOfDay()->diffInDays($d->copy()->startOfDay(), false) : null;
    $teksSisa = function ($h) {
        if ($h === null) return null;
        if ($h === 0) return 'Hari ini';
        $n = abs($h);
        $durasi = $n < 45 ? $n.' hari' : round($n / 30).' bulan';
        return $h < 0 ? 'Lewat '.$durasi : $durasi.' lagi';
    };
    $gayaStatus = [
        'terlewat' => ['pil' => 'bg-red-50 text-red-700 ring-red-600/15', 'titik' => 'bg-red-500', 'garis' => 'shadow-[inset_3px_0_0_0_#ef4444]'],
        'bulan_ini' => ['pil' => 'bg-amber-50 text-amber-800 ring-amber-600/20', 'titik' => 'bg-amber-500', 'garis' => 'shadow-[inset_3px_0_0_0_#f59e0b]'],
        '3_bulan' => ['pil' => 'bg-sky-50 text-sky-700 ring-sky-600/15', 'titik' => 'bg-sky-500', 'garis' => ''],
    ];

    // Avatar inisial: gelar depan/belakang dibuang, warna tetap per nama.
    $warnaAvatar = [
        'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-800',
        'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700', 'bg-teal-100 text-teal-700',
        'bg-indigo-100 text-indigo-700', 'bg-orange-100 text-orange-700',
    ];
    $avatar = function ($nama) use ($warnaAvatar) {
        $bersih = trim(explode(',', (string) $nama)[0]);
        $bersih = preg_replace('/^((dr|drs|dra|ir|h|hj|prof)\.?\s+)+/i', '', $bersih);
        $kata = preg_split('/\s+/', $bersih, -1, PREG_SPLIT_NO_EMPTY);
        $inisial = mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
        return ['inisial' => $inisial, 'warna' => $warnaAvatar[crc32((string) $nama) % count($warnaAvatar)]];
    };

    // "Pembina IV/a" -> kode "IV/a" (jadi badge berwarna per golongan) + nama pangkat.
    $warnaGol = [
        'I' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
        'II' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'III' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'IV' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
    ];
    $pecahGol = function ($teks) use ($warnaGol) {
        $teks = trim((string) $teks);
        if (! preg_match('/([IV]{1,3}\/[a-e])$/i', $teks, $m)) return ['kode' => null, 'pangkat' => $teks, 'warna' => ''];
        $kode = $m[1];
        $pangkat = trim(substr($teks, 0, -strlen($kode)), " -–");
        if ($pangkat === '') $pangkat = \App\Models\Pegawai::GOLONGAN[$kode] ?? '';
        $ruang = strtoupper(explode('/', $kode)[0]);
        return ['kode' => $kode, 'pangkat' => $pangkat, 'warna' => $warnaGol[$ruang] ?? $warnaGol['I']];
    };

    // ---------- Kelas yang dipakai berulang ----------
    $btn = 'inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:opacity-60';
    $btnPrimer = $btn.' bg-emerald-700 text-white shadow-sm shadow-emerald-900/20 hover:bg-emerald-800';
    $btnSekunder = $btn.' bg-white text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 hover:text-slate-900';
    $btnBahaya = $btn.' bg-red-600 text-white shadow-sm hover:bg-red-700';
    $kontrol = 'h-10 w-full rounded-lg border bg-white text-sm shadow-sm transition placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-600/10';
    $kontrolNetral = 'border-slate-300 text-slate-600 hover:border-slate-400';
    $kontrolAktif = 'border-emerald-600 bg-emerald-50/60 font-medium text-emerald-900';
    $btnIkon = 'inline-flex size-8 items-center justify-center rounded-lg transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600';

    // Ikon (Heroicons outline, MIT).
    $ikon = fn (string $d, string $kelas = 'size-4') => '<svg class="'.$kelas.'" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/></svg>';
    $i = [
        'tambah' => 'M12 4.5v15m7.5-7.5h-15',
        'unduh' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3',
        'proses' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'cari' => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'edit' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
        'hapus' => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'x' => 'M6 18 18 6M6 6l12 12',
        'bawah' => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'kiri' => 'M15.75 19.5 8.25 12l7.5-7.5',
        'kanan' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
    ];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">

<div class="content_web mx-auto max-w-screen-2xl px-4 py-8 text-slate-900 antialiased sm:px-6 lg:px-8"
     style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
     <video autoplay muted loop playsinline class="fixed inset-0 -z-10 h-full w-full object-cover">
    <source src="{{ asset('assets/video/sawah.mp4') }}" type="video/mp4">
</video>

    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Data Pegawai</h1>
            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-sm font-semibold tabular-nums text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
                  title="Total pegawai terdaftar">{{ number_format($totalPegawai, 0, ',', '.') }}</span>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
            <button type="button" data-buka-dialog="dialog-proses-kgb" class="{{ $btnSekunder }}">{!! $ikon($i['proses'], 'size-4 text-slate-500') !!} Proses KGB</button>
            <a href="{{ route('pegawai.export-excel', $aktif) }}" class="{{ $btnSekunder }}"
               title="Unduh data sesuai filter yang sedang aktif">{!! $ikon($i['unduh'], 'size-4 text-slate-500') !!} Export Excel</a>
            <a href="{{ route('pegawai.create') }}" class="{{ $btnPrimer }} order-first col-span-2 sm:order-none">{!! $ikon($i['tambah']) !!} Tambah pegawai</a>
        </div>
    </header>

    <div class="mt-6">
        @include('pegawai.partials.flash')
    </div>

    <form id="form-filter" method="GET" action="{{ route('pegawai.index') }}"
          class="mt-3 flex flex-col gap-2 md:flex-row md:items-center">
        @if ($kgbAktif !== '')
            <input type="hidden" name="kgb" value="{{ $kgbAktif }}">
        @endif

        <div class="relative md:w-96">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">{!! $ikon($i['cari']) !!}</span>
            <input type="search" name="q" id="cari" value="{{ $aktif['q'] ?? '' }}"
                   placeholder="Cari nama, NIP, atau jabatan" autocomplete="off" aria-label="Cari pegawai"
                   class="peer {{ $kontrol }} border-slate-300 pl-9 pr-9 text-slate-900 hover:border-slate-400">
            <kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-200 bg-slate-50 px-1.5 font-sans text-[11px] font-medium leading-5 text-slate-400 transition-opacity peer-focus:opacity-0 peer-[:not(:placeholder-shown)]:opacity-0 sm:block">/</kbd>
        </div>

        <div class="grid grid-cols-2 gap-2 md:flex">
            <div class="relative md:w-60">
                <select name="golongan" data-auto-submit aria-label="Golongan"
                        class="{{ $kontrol }} appearance-none pl-3 pr-8 {{ filled($aktif['golongan'] ?? null) ? $kontrolAktif : $kontrolNetral }}">
                    <option value="">Semua golongan</option>
                    @foreach (\App\Models\Pegawai::GOLONGAN as $kode => $nama)
                        <option value="{{ $kode }}" @selected(($aktif['golongan'] ?? '') === (string) $kode)>{{ $kode }} · {{ $nama }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-slate-400">{!! $ikon($i['bawah'], 'size-3.5') !!}</span>
            </div>
            <div class="relative md:w-40">
                <select name="mode" data-auto-submit aria-label="Mode KGB"
                        class="{{ $kontrol }} appearance-none pl-3 pr-8 {{ filled($aktif['mode'] ?? null) ? $kontrolAktif : $kontrolNetral }}">
                    <option value="">Semua mode</option>
                    <option value="auto" @selected(($aktif['mode'] ?? '') === 'auto')>Otomatis</option>
                    <option value="manual" @selected(($aktif['mode'] ?? '') === 'manual')>Manual</option>
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-slate-400">{!! $ikon($i['bawah'], 'size-3.5') !!}</span>
            </div>
        </div>

        <noscript><button type="submit" class="{{ $btnSekunder }}">Terapkan</button></noscript>

        @if ($chips)
            <div class="flex flex-wrap items-center gap-1.5 md:ml-2">
                @foreach ($chips as $kunci => $label)
                    <a href="{{ $urlIndex(array_diff_key($aktif, [$kunci => true])) }}"
                       class="inline-flex h-7 items-center gap-1 rounded-full bg-emerald-50 pl-3 pr-2 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20 transition hover:bg-emerald-100"
                       title="Hapus filter ini">
                        {{ $label }} {!! $ikon($i['x'], 'size-3.5 text-emerald-600') !!}
                    </a>
                @endforeach
                @if (count($chips) > 1)
                    <a href="{{ $urlIndex(['kgb' => $kgbAktif]) }}" class="px-1 text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline">Hapus semua</a>
                @endif
            </div>
        @endif
    </form>

    <div class="mt-5 w-full overflow-hidden rounded-xl bg-white/70 backdrop-blur-md shadow-[0_1px_2px_rgba(15,23,42,0.05),0_8px_24px_-12px_rgba(15,23,42,0.12)] ring-1 ring-slate-200">

        {{-- Kepala kartu: tab status KGB + jumlah hasil --}}
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 sm:px-5">
            <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Status KGB">
                @foreach ($tabs as $kode => $label)
                    @php
                        $terpilih = $kgbAktif === $kode;
                        $jumlah = $jumlahStatus[$kode === '' ? 'semua' : $kode] ?? null;
                    @endphp
                    <a href="{{ $urlIndex(array_merge($aktif, ['kgb' => $kode])) }}"
                       @if ($terpilih) aria-current="page" @endif
                       class="inline-flex shrink-0 items-center gap-2 border-b-2 py-3.5 text-sm transition {{ $terpilih ? 'border-emerald-600 font-semibold text-slate-900' : 'border-transparent font-medium text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
                        {{ $label }}
                        @if ($jumlah !== null)
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums {{ $kode === 'terlewat' && $jumlah > 0 ? 'bg-red-100 text-red-700' : ($terpilih ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">{{ $jumlah }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            @if ($pegawais->total() > 0)
                <p class="hidden shrink-0 text-sm tabular-nums text-slate-500 lg:block">
                    {{ $pegawais->firstItem() }}–{{ $pegawais->lastItem() }} dari {{ $pegawais->total() }}
                </p>
            @endif
        </div>

        @if ($pegawais->isEmpty())
            <div class="px-6 py-16 text-center">
                @if ($totalPegawai === 0)
                    <p class="font-semibold text-slate-900">Belum ada data pegawai</p>
                    <p class="mt-1 text-sm text-slate-500">Mulai dengan menambahkan pegawai pertama.</p>
                    <a href="{{ route('pegawai.create') }}" class="{{ $btnPrimer }} mt-5">{!! $ikon($i['tambah']) !!} Tambah pegawai</a>
                @else
                    <p class="font-semibold text-slate-900">Tidak ada pegawai yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci atau longgarkan filternya.</p>
                    <a href="{{ route('pegawai.index') }}" class="{{ $btnSekunder }} mt-5">Hapus semua filter</a>
                @endif
            </div>
        @else
            {{-- Tabel (tablet & desktop) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="whitespace-nowrap border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="py-3 pl-5 pr-3 font-semibold">No</th>
                            <th scope="col" class="py-3 pl-5 pr-3 font-semibold">Nip</th>
                            <th scope="col" class="py-3 pl-5 pr-3 font-semibold">Nama</th>
                            <th scope="col" class="px-3 py-3 font-semibold">GOL/PANGKAT CPNS</th>
                            <th scope="col" class="px-3 py-3 font-semibold">Jabatan</th>
                            <th scope="col" class="px-3 py-3 font-semibold">GOL/PANGKAT(TERAKHIR)</th>
                            <th scope="col" class="px-3 py-3 font-semibold">Masa kerja</th>
                            <th scope="col" class="px-3 py-3 font-semibold">KGB terakhir</th>
                            <th scope="col" class="px-3 py-3 font-semibold">KGB berikutnya</th>
                            <th scope="col" class="px-3 py-3 font-semibold">Mode</th>
                            <th scope="col" class="py-3 pl-3 pr-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pegawais as $pegawai)
                            @php
                                $status = $gayaStatus[$pegawai->status_kgb] ?? null;
                                $sisa = $status ? $teksSisa($sisaHari($pegawai->kgb_berikutnya)) : null;
                                $av = $avatar($pegawai->nama);
                                $gol = $pecahGol($pegawai->gol_pangkat_terakhir);
                            @endphp
                            <tr data-href="{{ route('pegawai.show', $pegawai) }}" class="group cursor-pointer transition-colors hover:bg-emerald-50/40">
                                <td class="py-3.5 pl-5 pr-3 tabular-nums text-slate-900 {{ $status['garis'] ?? '' }}">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-3 py-3.5 tabular-nums text-slate-900 ">
                                        {!! $nip($pegawai->nip) !!}
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <a href="{{ route('pegawai.show', $pegawai) }}" class="font-semibold text-slate-900 hover:underline group-hover:text-emerald-800 focus-visible:underline focus-visible:outline-none">{{ $pegawai->nama }}</a>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5">
                                    <div class="flex items-center gap-2">
                                        @if ($gol['kode'])
                                            <span class="rounded-md px-1.5 py-0.5 text-xs font-bold tabular-nums ring-1 ring-inset {{ $gol['warna'] }}">{{ $gol['kode'] }}</span>
                                        @endif
                                        <span class="font-medium text-slate-800">{{ $gol['pangkat'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs tabular-nums text-slate-500">TMT {{ $tgl($pegawai->tmt_pangkat_terakhir) }}</div>
                                </td>
                                <td class="max-w-xs px-3 py-3.5 text-slate-600">{{ $pegawai->jabatan }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5">{{ $pegawai->gol_pangkat_terakhir }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5 tabular-nums text-slate-600">{{ $pegawai->masa_kerja_label }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5 tabular-nums text-slate-600">{{ $tgl($pegawai->kgb) }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <div class="tabular-nums {{ $status ? 'font-semibold text-slate-900' : 'text-slate-600' }}">{{ $tgl($pegawai->kgb_berikutnya) }}</div>
                                    @if ($sisa)
                                        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $status['pil'] }}">
                                            <span class="size-1.5 rounded-full {{ $status['titik'] }}"></span>{{ $sisa }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    @if ($pegawai->kgb_mode === 'manual')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-600/15">
                                            <span class="size-1.5 rounded-full bg-violet-500"></span>Manual
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>Otomatis
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-2 pl-3 pr-4">
                                    <div class="flex justify-end gap-1 text-slate-400">
                                        <a href="{{ route('pegawai.edit', $pegawai) }}" class="{{ $btnIkon }} hover:bg-emerald-100 hover:text-emerald-800 group-hover:text-slate-600"
                                           title="Edit" aria-label="Edit {{ $pegawai->nama }}">{!! $ikon($i['edit']) !!}</a>
                                        <button type="button" class="{{ $btnIkon }} hover:bg-red-100 hover:text-red-700 group-hover:text-slate-600"
                                                data-hapus-url="{{ route('pegawai.destroy', $pegawai) }}" data-hapus-nama="{{ $pegawai->nama }}"
                                                title="Hapus" aria-label="Hapus {{ $pegawai->nama }}">{!! $ikon($i['hapus']) !!}</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Daftar ringkas (ponsel) --}}
            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($pegawais as $pegawai)
                    @php
                        $status = $gayaStatus[$pegawai->status_kgb] ?? null;
                        $sisa = $status ? $teksSisa($sisaHari($pegawai->kgb_berikutnya)) : null;
                        $av = $avatar($pegawai->nama);
                        $gol = $pecahGol($pegawai->gol_pangkat_terakhir);
                    @endphp
                    <li>
                        <a href="{{ route('pegawai.show', $pegawai) }}" class="flex gap-3 px-4 py-4 active:bg-emerald-50/50 {{ $status['garis'] ?? '' }}">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $av['warna'] }}" aria-hidden="true">{{ $av['inisial'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900">{{ $pegawai->nama }}</p>
                                <p class="mt-0.5 text-xs tabular-nums text-slate-500">{!! $nip($pegawai->nip) !!}</p>
                                <p class="mt-2 truncate text-sm text-slate-600">{{ $pegawai->jabatan }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                                    @if ($gol['kode'])
                                        <span class="rounded-md px-1.5 py-0.5 font-bold ring-1 ring-inset {{ $gol['warna'] }}">{{ $gol['kode'] }}</span>
                                    @endif
                                    <span class="text-slate-500">{{ $pegawai->masa_kerja_label }}</span>
                                    @if ($sisa)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-semibold ring-1 ring-inset {{ $status['pil'] }}">
                                            <span class="size-1.5 rounded-full {{ $status['titik'] }}"></span>KGB {{ lcfirst($sisa) }}
                                        </span>
                                    @else
                                        <span class="text-slate-500">· KGB {{ $tgl($pegawai->kgb_berikutnya) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Paginasi --}}
            @if ($pegawais->hasPages())
                @php
                    $hal = $pegawais->currentPage();
                    $akhir = $pegawais->lastPage();
                    $nomor = array_unique(array_filter([1, $hal - 1, $hal, $hal + 1, $akhir], fn ($n) => $n >= 1 && $n <= $akhir));
                    sort($nomor);
                    $halBtn = 'h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-medium tabular-nums transition';
                @endphp
                <nav class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:px-5" aria-label="Halaman">
                    <p class="text-sm tabular-nums text-slate-500">Halaman <span class="font-semibold text-slate-800">{{ $hal }}</span> dari <span class="font-semibold text-slate-800">{{ $akhir }}</span></p>
                    <div class="flex items-center gap-1">
                        @if ($pegawais->onFirstPage())
                            <span class="{{ $halBtn }} inline-flex text-slate-300" aria-hidden="true">{!! $ikon($i['kiri']) !!}</span>
                        @else
                            <a href="{{ $pegawais->previousPageUrl() }}" rel="prev" class="{{ $halBtn }} inline-flex text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm hover:ring-1 hover:ring-slate-200" aria-label="Halaman sebelumnya">{!! $ikon($i['kiri']) !!}</a>
                        @endif

                        @php $sebelumnya = 0; @endphp
                        @foreach ($nomor as $n)
                            @if ($n - $sebelumnya > 1)
                                <span class="{{ $halBtn }} hidden text-slate-400 sm:inline-flex">…</span>
                            @endif
                            @if ($n === $hal)
                                <span class="{{ $halBtn }} inline-flex bg-emerald-700 text-white shadow-sm" aria-current="page">{{ $n }}</span>
                            @else
                                <a href="{{ $pegawais->url($n) }}" class="{{ $halBtn }} hidden text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm hover:ring-1 hover:ring-slate-200 sm:inline-flex">{{ $n }}</a>
                            @endif
                            @php $sebelumnya = $n; @endphp
                        @endforeach

                        @if ($pegawais->hasMorePages())
                            <a href="{{ $pegawais->nextPageUrl() }}" rel="next" class="{{ $halBtn }} inline-flex text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm hover:ring-1 hover:ring-slate-200" aria-label="Halaman berikutnya">{!! $ikon($i['kanan']) !!}</a>
                        @else
                            <span class="{{ $halBtn }} inline-flex text-slate-300" aria-hidden="true">{!! $ikon($i['kanan']) !!}</span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </div>
</div>

{{-- ================= Dialog konfirmasi ================= --}}
<dialog id="dialog-hapus" aria-labelledby="judul-hapus"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-white p-0 text-left shadow-2xl ring-1 ring-slate-900/5 backdrop:bg-slate-900/50"
        style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
    <form method="POST" action="" class="p-6">
        @csrf
        @method('DELETE')
        <div class="flex size-10 items-center justify-center rounded-full bg-red-100 text-red-600">{!! $ikon($i['hapus'], 'size-5') !!}</div>
        <h2 id="judul-hapus" class="mt-4 text-lg font-bold text-slate-900">Hapus data pegawai?</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Data <strong class="font-semibold text-slate-900" data-isi="nama"></strong> beserta seluruh riwayat KGB-nya
            akan dihapus permanen dan tidak bisa dikembalikan.
        </p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" data-tutup-dialog class="{{ $btnSekunder }}" autofocus>Batal</button>
            <button type="submit" class="{{ $btnBahaya }}" data-teks-proses="Menghapus…">Ya, hapus</button>
        </div>
    </form>
</dialog>

<dialog id="dialog-proses-kgb" aria-labelledby="judul-proses"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-white p-0 text-left shadow-2xl ring-1 ring-slate-900/5 backdrop:bg-slate-900/50"
        style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
    <form method="POST" action="{{ route('pegawai.kgb.proses-semua') }}" class="p-6">
        @csrf
        <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">{!! $ikon($i['proses'], 'size-5') !!}</div>
        <h2 id="judul-proses" class="mt-4 text-lg font-bold text-slate-900">Proses KGB otomatis sekarang?</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Tanggal KGB semua pegawai bermode <span class="font-semibold text-slate-900">Otomatis</span> akan diperbarui.
            Pegawai bermode Manual tidak ikut diproses.
        </p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" data-tutup-dialog class="{{ $btnSekunder }}" autofocus>Batal</button>
            <button type="submit" class="{{ $btnPrimer }}" data-teks-proses="Memproses…">Jalankan</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const formFilter = document.getElementById('form-filter');

    // Kirim otomatis saat dropdown berubah; buang parameter kosong agar URL bersih.
    formFilter.querySelectorAll('[data-auto-submit]').forEach((el) =>
        el.addEventListener('change', () => formFilter.requestSubmit()));
    formFilter.addEventListener('submit', () =>
        formFilter.querySelectorAll('input, select').forEach((el) => { if (!el.value) el.disabled = true; }));

    // Tekan "/" di mana saja untuk langsung mencari.
    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey) return;
        if (e.target.closest?.('input, textarea, select, [contenteditable]')) return;
        e.preventDefault();
        const cari = document.getElementById('cari');
        cari.focus();
        cari.select();
    });

    // Klik baris tabel = buka detail (kecuali klik tombol/link, atau sedang menyeleksi teks).
    document.querySelectorAll('tr[data-href]').forEach((tr) =>
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a, button, form')) return;
            if (window.getSelection().toString()) return;
            if (e.metaKey || e.ctrlKey) window.open(tr.dataset.href, '_blank');
            else window.location.href = tr.dataset.href;
        }));

    // Dialog
    document.querySelectorAll('[data-buka-dialog]').forEach((btn) =>
        btn.addEventListener('click', () => document.getElementById(btn.dataset.bukaDialog).showModal()));

    document.querySelectorAll('[data-hapus-url]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const dialog = document.getElementById('dialog-hapus');
            dialog.querySelector('form').action = btn.dataset.hapusUrl;
            dialog.querySelector('[data-isi="nama"]').textContent = btn.dataset.hapusNama;
            dialog.showModal();
        }));

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
        dialog.querySelectorAll('[data-tutup-dialog]').forEach((b) => b.addEventListener('click', () => dialog.close()));
        dialog.querySelector('form').addEventListener('submit', (e) => {
            const tombol = e.target.querySelector('[type="submit"]');
            tombol.dataset.teksAsli = tombol.textContent;
            tombol.disabled = true;
            tombol.textContent = tombol.dataset.teksProses;
        });
    });

    // Saat kembali lewat tombol Back, pulihkan elemen yang tadi dinonaktifkan.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form :disabled').forEach((el) => {
            el.disabled = false;
            if (el.dataset.teksAsli) el.textContent = el.dataset.teksAsli;
        });
        document.querySelectorAll('dialog[open]').forEach((d) => d.close());
    });
})();
</script>
@endsection