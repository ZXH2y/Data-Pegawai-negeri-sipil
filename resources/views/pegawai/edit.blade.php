@extends('layouts.app')

@section('title', 'Edit Pegawai')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
     <video autoplay muted loop playsinline class="fixed inset-0 -z-10 h-full w-full object-cover">
    <source src="{{ asset('assets/video/sawah.mp4') }}" type="video/mp4">
</video>
    <div class="mb-6">
        <div class="mb-8">
            <a href="{{ route('pegawai.show', $pegawai) }}" class="h-10 w-10 p-4 rounded-2xl bg-white text-sm text-gray-500 hover:bg-gray-200 transition-all duration-300">&larr; Kembali ke detail</a>
        </div>
        <h1 class="mt-2 text-2xl font-semibold text-gray-800">Edit Pegawai</h1>
        <p class="text-sm text-gray-500">{{ $pegawai->nama }} — NIP {{ $pegawai->nip }}</p>
    </div>

    @include('pegawai.partials.flash')

    <form method="POST" action="{{ route('pegawai.update', $pegawai) }}" class="rounded-xl bg-white/70 p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @method('PUT')
        @include('pegawai.partials.form')

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('pegawai.show', $pegawai) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan perubahan</button>
        </div>
    </form>
</div>
@endsection
