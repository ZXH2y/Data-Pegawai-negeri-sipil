@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
@php
    $input = 'rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
@endphp
<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Data Pegawai</h1>
            <p class="text-sm text-gray-500">Total {{ $pegawais->total() }} pegawai</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('pegawai.kgb.proses-semua') }}"
                  onsubmit="return confirm('Jalankan update KGB otomatis untuk semua pegawai bermode AUTO sekarang?')">
                @csrf
                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">
                    Jalankan update KGB
                </button>
            </form>
            <a href="{{ route('pegawai.export-pdf', array_merge($filter, ['preview' => 1])) }}" target="_blank"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Preview PDF
            </a>
            <a href="{{ route('pegawai.export-pdf', $filter) }}"
               class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                Export PDF
            </a>
            <a href="{{ route('pegawai.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + Tambah Pegawai
            </a>
        </div>
    </div>

    @include('pegawai.partials.flash')

    {{-- Filter --}}
    <form method="GET" action="{{ route('pegawai.index') }}" class="mb-4 grid grid-cols-1 gap-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 md:grid-cols-5">
        <input type="search" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Cari NIP / nama / jabatan" class="{{ $input }} md:col-span-2">

        <select name="golongan" class="{{ $input }}">
            <option value="">Semua golongan</option>
            @foreach (\App\Models\Pegawai::GOLONGAN as $kode => $nama)
                <option value="{{ $kode }}" @selected(($filter['golongan'] ?? '') === $kode)>{{ $kode }} - {{ $nama }}</option>
            @endforeach
        </select>

        <select name="kgb" class="{{ $input }}">
            <option value="">Semua status KGB</option>
            <option value="bulan_ini" @selected(($filter['kgb'] ?? '') === 'bulan_ini')>Jatuh tempo bulan ini</option>
            <option value="3_bulan" @selected(($filter['kgb'] ?? '') === '3_bulan')>Jatuh tempo ≤ 3 bulan</option>
            <option value="terlewat" @selected(($filter['kgb'] ?? '') === 'terlewat')>Terlewat</option>
        </select>

        <div class="flex gap-2">
            <select name="mode" class="{{ $input }} flex-1">
                <option value="">Semua mode</option>
                <option value="auto" @selected(($filter['mode'] ?? '') === 'auto')>Auto</option>
                <option value="manual" @selected(($filter['mode'] ?? '') === 'manual')>Manual</option>
            </select>
            <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">NIP / Nama</th>
                    <th class="px-4 py-3">Gol. Terakhir</th>
                    <th class="px-4 py-3">Jabatan</th>
                    <th class="px-4 py-3">Masa Kerja</th>
                    <th class="px-4 py-3">KGB Terakhir</th>
                    <th class="px-4 py-3">KGB Berikutnya</th>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pegawais as $pegawai)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $pegawais->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('pegawai.show', $pegawai) }}" class="font-medium text-gray-900 hover:text-blue-600">{{ $pegawai->nama }}</a>
                            <div class="font-mono text-xs text-gray-500">{{ $pegawai->nip }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ $pegawai->gol_pangkat_terakhir }}
                            <div class="text-xs text-gray-500">TMT {{ $pegawai->tmt_pangkat_terakhir?->format('d-m-Y') }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $pegawai->jabatan }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $pegawai->masa_kerja_label }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $pegawai->kgb?->format('d-m-Y') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $pegawai->kgb_berikutnya?->format('d-m-Y') ?? '-' }}
                            @include('pegawai.partials.badge-kgb', ['status' => $pegawai->status_kgb])
                        </td>
                        <td class="px-4 py-3">
                            @if ($pegawai->kgb_mode === 'manual')
                                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Manual</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Auto</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('pegawai.show', $pegawai) }}" class="text-blue-600 hover:underline">Detail</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('pegawai.edit', $pegawai) }}" class="text-amber-600 hover:underline">Edit</a>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="{{ route('pegawai.destroy', $pegawai) }}" class="inline"
                                  onsubmit="return confirm('Hapus data {{ addslashes($pegawai->nama) }} beserta riwayat KGB-nya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-500">Belum ada data pegawai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pegawais->links() }}
    </div>
</div>
@endsection
