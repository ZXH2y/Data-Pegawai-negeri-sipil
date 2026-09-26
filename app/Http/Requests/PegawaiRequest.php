<?php

namespace App\Http\Requests;

use App\Models\Pegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ganti dengan policy/gate bila sudah ada autentikasi
    }

    public function rules(): array
    {
        $pegawai = $this->route('pegawai');
        $golongan = array_keys(Pegawai::GOLONGAN);

        $rules = [
            'nip' => ['required', 'digits:18', Rule::unique('pegawais', 'nip')->ignore($pegawai?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'gol_pangkat_cpns' => ['required', Rule::in($golongan)],
            'jabatan' => ['required', 'string', 'max:255'],
            'gol_pangkat_terakhir' => ['required', Rule::in($golongan)],
            'tmt_pangkat_terakhir' => ['required', 'date'],
            'tmt_cpns' => ['nullable', 'date', 'before_or_equal:tmt_pangkat_terakhir', 'before_or_equal:today'],
            // Dipakai hanya bila tmt_cpns kosong (masa kerja diisi manual).
            'masa_kerja_tahun' => ['nullable', 'integer', 'min:0', 'max:60'],
            'masa_kerja_bulan' => ['nullable', 'integer', 'min:0', 'max:11'],
        ];

        // KGB terakhir hanya boleh diisi saat membuat data baru.
        // Perubahan KGB setelahnya lewat form "Update KGB manual" agar tercatat di riwayat.
        if ($this->isMethod('post')) {
            $rules['kgb'] = ['nullable', 'date', 'before_or_equal:today'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'nip' => 'NIP',
            'gol_pangkat_cpns' => 'golongan/pangkat CPNS',
            'gol_pangkat_terakhir' => 'golongan/pangkat terakhir',
            'tmt_pangkat_terakhir' => 'TMT pangkat terakhir',
            'tmt_cpns' => 'TMT CPNS',
            'kgb' => 'KGB terakhir',
            'masa_kerja_tahun' => 'masa kerja (tahun)',
            'masa_kerja_bulan' => 'masa kerja (bulan)',
        ];
    }

    public function messages(): array
    {
        return [
            'nip.digits' => 'NIP harus 18 digit angka.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
        ];
    }

    /** Kosongkan string masa kerja agar default 0 dipakai. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nip' => preg_replace('/\s+/', '', (string) $this->input('nip')),
            'masa_kerja_tahun' => $this->input('masa_kerja_tahun') === null || $this->input('masa_kerja_tahun') === '' ? 0 : $this->input('masa_kerja_tahun'),
            'masa_kerja_bulan' => $this->input('masa_kerja_bulan') === null || $this->input('masa_kerja_bulan') === '' ? 0 : $this->input('masa_kerja_bulan'),
        ]);
    }
}
