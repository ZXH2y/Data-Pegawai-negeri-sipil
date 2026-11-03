@extends('layouts.app')

@section('title', 'Detail Pegawai')

@section('content')
    @php
        $input =
            'mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
        $label = 'block text-sm font-medium text-gray-700';
        $tgl = fn($d) => $d?->translatedFormat('d F Y') ?? '-';
    @endphp
    <div class="mx-auto max-w-6xl px-4 py-6" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        <video autoplay muted loop playsinline class="fixed inset-0 -z-10 h-full w-full object-cover">
            <source src="{{ asset('assets/video/sawah.mp4') }}" type="video/mp4">
        </video>
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="mb-6">
                    <a href="{{ route('pegawai.index') }}"
                        class="h-10 w-10 p-4 rounded-2xl bg-white text-sm text-gray-500 hover:bg-gray-200 transition-all duration-300">&larr;
                        Kembali ke daftar</a>
                </div>
                <h1 class="mt-2 text-2xl font-semibold text-gray-800">{{ $pegawai->nama }}</h1>
                <p class="font-mono text-sm text-gray-500">NIP {{ $pegawai->nip }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pegawai.export-detail-excel', $pegawai) }}"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Excel</a>
                <a href="{{ route('pegawai.edit', $pegawai) }}"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">Edit</a>
            </div>
        </div>

        @include('pegawai.partials.flash')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Data pokok --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Data Kepegawaian</h2>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">Jabatan</dt>
                        <dd class="font-medium">{{ $pegawai->jabatan }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Gol/Pangkat CPNS</dt>
                        <dd class="font-medium">{{ $pegawai->gol_pangkat_cpns_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Gol/Pangkat terakhir</dt>
                        <dd class="font-medium">{{ $pegawai->gol_pangkat_terakhir_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">TMT pangkat terakhir</dt>
                        <dd class="font-medium">{{ $tgl($pegawai->tmt_pangkat_terakhir) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">TMT CPNS</dt>
                        <dd class="font-medium">{{ $tgl($pegawai->tmt_cpns) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Masa kerja</dt>
                        <dd class="font-medium">
                            {{ $pegawai->masa_kerja_label }}
                            @unless ($pegawai->tmt_cpns)
                                <span class="text-xs font-normal text-gray-400">(input manual)</span>
                            @endunless
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Ringkasan KGB --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">KGB</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">KGB terakhir</dt>
                        <dd class="font-medium">{{ $tgl($pegawai->kgb) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">KGB berikutnya</dt>
                        <dd class="font-medium">
                            {{ $tgl($pegawai->kgb_berikutnya) }}
                            @include('pegawai.partials.badge-kgb', ['status' => $pegawai->status_kgb])
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Mode</dt>
                        <dd>
                            @if ($pegawai->kgb_mode === 'manual')
                                <span
                                    class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Manual
                                    (dikunci)</span>
                                <p class="mt-1 text-xs text-gray-500">Scheduler tidak akan mengubah KGB pegawai ini.</p>
                                <form method="POST" action="{{ route('pegawai.kgb.otomatis', $pegawai) }}" class="mt-2"
                                    onsubmit="return confirm('Kembalikan ke mode otomatis? KGB berikutnya akan dihitung ulang (KGB terakhir + 2 tahun).')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="text-xs font-medium text-blue-600 hover:underline">Kembalikan ke mode
                                        otomatis</button>
                                </form>
                            @else
                                <span
                                    class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Otomatis</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Form update KGB manual --}}
        <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 class="mb-1 text-lg font-semibold text-gray-800">Update KGB manual</h2>
            <p class="mb-4 text-sm text-gray-500">Gunakan bila SK KGB terbit dengan tanggal berbeda dari perhitungan sistem.
            </p>

            <form method="POST" action="{{ route('pegawai.kgb.store', $pegawai) }}"
                class="grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf
                <div>
                    <label for="kgb_baru" class="{{ $label }}">Tanggal KGB baru <span
                            class="text-red-500">*</span></label>
                    <input type="date" id="kgb_baru" name="kgb_baru" value="{{ old('kgb_baru') }}"
                        class="{{ $input }}" required>
                </div>
                <div>
                    <label for="kgb_berikutnya" class="{{ $label }}">KGB berikutnya</label>
                    <input type="date" id="kgb_berikutnya" name="kgb_berikutnya" value="{{ old('kgb_berikutnya') }}"
                        class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500">Kosong = +2 tahun. Hanya berlaku jika dikunci.</p>
                </div>
                <div class="md:col-span-2">
                    <label for="keterangan" class="{{ $label }}">Keterangan</label>
                    <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}"
                        placeholder="mis. SK No. 800/123/2026" class="{{ $input }}">
                </div>
                <div class="flex items-center justify-between gap-4 md:col-span-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="kunci_manual" value="0">
                        <input type="checkbox" name="kunci_manual" value="1"
                            class="rounded border-gray-300 text-blue-600" @checked(old('kunci_manual', '1') === '1')>
                        Kunci sebagai manual (scheduler tidak menimpa)
                    </label>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan
                        KGB</button>
                </div>
            </form>
        </div>

        {{-- Riwayat --}}
        <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <h2 class="px-6 pt-6 text-lg font-semibold text-gray-800">Riwayat KGB</h2>
            <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-6 py-3">Dicatat</th>
                        <th class="px-6 py-3">KGB lama</th>
                        <th class="px-6 py-3">KGB baru</th>
                        <th class="px-6 py-3">KGB berikutnya</th>
                        <th class="px-6 py-3">Sumber</th>
                        <th class="px-6 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pegawai->kgbHistories as $h)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-500">
                                {{ $h->created_at->format('d-m-Y H:i') }}</td>
                            <td class="whitespace-nowrap px-6 py-3">{{ $h->kgb_lama?->format('d-m-Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 font-medium">{{ $h->kgb_baru->format('d-m-Y') }}</td>
                            <td class="whitespace-nowrap px-6 py-3">{{ $h->kgb_berikutnya_baru->format('d-m-Y') }}</td>
                            <td class="px-6 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium {{ $h->sumber === 'auto' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ ucfirst($h->sumber) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $h->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat perubahan
                                KGB.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('pegawai.destroy', $pegawai) }}"
            class="mt-6 flex justify-end items-center"
            onsubmit="return confirm('Hapus data pegawai ini beserta riwayat KGB-nya?')">
            @csrf
            @method('DELETE')
            <div class="bg-white h-14 md:h-10 w-30 md:w-45 flex items-center rounded-xl p-2 md:pl-5 hover:bg-red-300">
                <button type="submit"
                    class="py-10 text-sm text-red-600 hover:text-white transition-all duration-200">Hapus
                    data
                    pegawai</button>
            </div>
        </form>
    </div>
@endsection
