{{-- Pemakaian: @include('pegawai.partials.badge-kgb', ['status' => $pegawai->status_kgb]) --}}
@php
    $badge = match ($status) {
        'terlewat'  => ['Terlewat', 'bg-red-100 text-red-700'],
        'bulan_ini' => ['Bulan ini', 'bg-amber-100 text-amber-800'],
        'segera'    => ['≤ 3 bulan', 'bg-sky-100 text-sky-700'],
        default     => null,
    };
@endphp
@if ($badge)
    <span class="ml-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[1] }}">{{ $badge[0] }}</span>
@endif
