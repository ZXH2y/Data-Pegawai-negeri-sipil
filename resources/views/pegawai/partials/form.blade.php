{{-- Dipakai oleh create & edit. Variabel: $pegawai (Pegawai baru atau yang diedit) --}}
@php
    $input = 'mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
    $label = 'block text-sm font-medium text-gray-700';
    $err   = 'mt-1 text-xs text-red-600';
    $tgl   = fn ($v) => $v?->format('Y-m-d');
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    {{-- NIP --}}
    <div>
        <label for="nip" class="{{ $label }}">NIP <span class="text-red-500">*</span></label>
        <input type="text" id="nip" name="nip" inputmode="numeric" maxlength="18"
               value="{{ old('nip', $pegawai->nip) }}" class="{{ $input }}" required>
        @error('nip') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Nama --}}
    <div>
        <label for="nama" class="{{ $label }}">Nama lengkap <span class="text-red-500">*</span></label>
        <input type="text" id="nama" name="nama" value="{{ old('nama', $pegawai->nama) }}" class="{{ $input }}" required>
        @error('nama') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Gol CPNS --}}
    <div>
        <label for="gol_pangkat_cpns" class="{{ $label }}">Gol/Pangkat CPNS <span class="text-red-500">*</span></label>
        <select id="gol_pangkat_cpns" name="gol_pangkat_cpns" class="{{ $input }}">
            <option value="">— pilih —</option>
            @foreach (\App\Models\Pegawai::GOLONGAN as $kode => $nama)
                <option value="{{ $kode }}" @selected(old('gol_pangkat_cpns', $pegawai->gol_pangkat_cpns) === $kode)>
                    {{ $kode }} - {{ $nama }}
                </option>
            @endforeach
        </select>
        @error('gol_pangkat_cpns') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Jabatan --}}
    <div>
        <label for="jabatan" class="{{ $label }}">Jabatan <span class="text-red-500">*</span></label>
        <input type="text" id="jabatan" name="jabatan" value="{{ old('jabatan', $pegawai->jabatan) }}" class="{{ $input }}" required>
        @error('jabatan') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Gol terakhir --}}
    <div>
        <label for="gol_pangkat_terakhir" class="{{ $label }}">Gol/Pangkat terakhir <span class="text-red-500">*</span></label>
        <select id="gol_pangkat_terakhir" name="gol_pangkat_terakhir" class="{{ $input }}">
            <option value="">— pilih —</option>
            @foreach (\App\Models\Pegawai::GOLONGAN as $kode => $nama)
                <option value="{{ $kode }}" @selected(old('gol_pangkat_terakhir', $pegawai->gol_pangkat_terakhir) === $kode)>
                    {{ $kode }} - {{ $nama }}
                </option>
            @endforeach
        </select>
        @error('gol_pangkat_terakhir') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- TMT pangkat terakhir --}}
    <div>
        <label for="tmt_pangkat_terakhir" class="{{ $label }}">TMT pangkat terakhir <span class="text-red-500">*</span></label>
        <input type="date" id="tmt_pangkat_terakhir" name="tmt_pangkat_terakhir"
               value="{{ old('tmt_pangkat_terakhir', $tgl($pegawai->tmt_pangkat_terakhir)) }}" class="{{ $input }}">
        @error('tmt_pangkat_terakhir') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- TMT CPNS --}}
    <div>
        <label for="tmt_cpns" class="{{ $label }}">TMT CPNS</label>
        <input type="date" id="tmt_cpns" name="tmt_cpns"
               value="{{ old('tmt_cpns', $tgl($pegawai->tmt_cpns)) }}" class="{{ $input }}">
        <p class="mt-1 text-xs text-gray-500">Jika diisi, masa kerja dihitung otomatis dari tanggal ini.</p>
        @error('tmt_cpns') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Masa kerja manual --}}
    <div>
        <span class="{{ $label }}">Masa kerja (manual)</span>
        <div class="mt-1 flex gap-2">
            <div class="flex-1">
                <input type="number" name="masa_kerja_tahun" min="0" max="60" placeholder="Tahun"
                       value="{{ old('masa_kerja_tahun', $pegawai->masa_kerja_tahun) }}" class="{{ $input }} !mt-0">
            </div>
            <div class="flex-1">
                <input type="number" name="masa_kerja_bulan" min="0" max="11" placeholder="Bulan"
                       value="{{ old('masa_kerja_bulan', $pegawai->masa_kerja_bulan) }}" class="{{ $input }} !mt-0">
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-500">Hanya dipakai bila TMT CPNS kosong.</p>
        @error('masa_kerja_tahun') <p class="{{ $err }}">{{ $message }}</p> @enderror
        @error('masa_kerja_bulan') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- KGB terakhir: hanya saat tambah data --}}
    @if (! $pegawai->exists)
        <div>
            <label for="kgb" class="{{ $label }}">KGB terakhir</label>
            <input type="date" id="kgb" name="kgb" value="{{ old('kgb') }}" class="{{ $input }}">
            <p class="mt-1 text-xs text-gray-500">
                Kosongkan bila belum pernah KGB — KGB berikutnya dihitung dari TMT pangkat terakhir + 2 tahun.
            </p>
            @error('kgb') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>
    @else
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-sm text-gray-600">
            <p><span class="font-medium">KGB terakhir:</span> {{ $pegawai->kgb?->translatedFormat('d F Y') ?? '-' }}</p>
            <p><span class="font-medium">KGB berikutnya:</span> {{ $pegawai->kgb_berikutnya?->translatedFormat('d F Y') ?? '-' }}</p>
            <p class="mt-1 text-xs">Ubah KGB melalui form <em>Update KGB manual</em> di halaman detail agar tercatat di riwayat.</p>
        </div>
    @endif
</div>
