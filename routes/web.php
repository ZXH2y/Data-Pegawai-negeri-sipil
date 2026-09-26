<?php

use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PegawaiKgbController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pegawai');

// Taruh route khusus SEBELUM Route::resource.
Route::get('pegawai/export/pdf', [PegawaiController::class, 'exportPdf'])->name('pegawai.export-pdf');
Route::post('pegawai/kgb/proses', [PegawaiKgbController::class, 'prosesSemua'])->name('pegawai.kgb.proses-semua');

Route::resource('pegawai', PegawaiController::class);

Route::get('pegawai/{pegawai}/pdf', [PegawaiController::class, 'exportDetailPdf'])->name('pegawai.export-detail-pdf');
Route::post('pegawai/{pegawai}/kgb', [PegawaiKgbController::class, 'store'])->name('pegawai.kgb.store');
Route::patch('pegawai/{pegawai}/kgb/otomatis', [PegawaiKgbController::class, 'aktifkanOtomatis'])->name('pegawai.kgb.otomatis');

// Bila nanti ada login, bungkus semua route di atas dengan:
// Route::middleware('auth')->group(function () { ... });
