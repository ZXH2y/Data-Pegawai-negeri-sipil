<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler KGB (Laravel 11/12)
|--------------------------------------------------------------------------
| TMT KGB PNS selalu tanggal 1, jadi cukup dijalankan tiap tanggal 1.
| Command ini idempotent (aman dijalankan berulang), jadi kalau server
| sempat mati di tanggal 1, ganti ->monthlyOn(...) dengan ->daily()
| supaya ketinggalan langsung terkejar keesokan harinya.
*/
Schedule::command('pegawai:update-kgb')
    ->monthlyOn(1, '01:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/kgb-scheduler.log'));
