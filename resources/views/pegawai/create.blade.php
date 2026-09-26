@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('pegawai.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke daftar</a>
        <h1 class="mt-2 text-2xl font-semibold text-gray-800">Tambah Pegawai</h1>
    </div>

    @include('pegawai.partials.flash')

    <form method="POST" action="{{ route('pegawai.store') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @include('pegawai.partials.form')

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('pegawai.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>
@endsection
