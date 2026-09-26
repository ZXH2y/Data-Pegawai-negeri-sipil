<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 20)->unique();
            $table->string('nama');
            $table->string('gol_pangkat_cpns', 50)->nullable();
            $table->string('jabatan');
            $table->string('gol_pangkat_terakhir', 50);
            $table->date('tmt_pangkat_terakhir')->nullable();
            $table->date('tmt_cpns')->nullable();        
            $table->date('kgb')->nullable();             
            $table->date('kgb_berikutnya')->nullable();    
            $table->integer('masa_kerja_tahun')->default(0);
            $table->integer('masa_kerja_bulan')->default(0);
            $table->enum('kgb_mode', ['auto', 'manual'])->default('auto'); 
            $table->timestamps();
        });

        Schema::create('pegawai_kgb_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained()->cascadeOnDelete();
            $table->date('kgb_lama')->nullable();
            $table->date('kgb_baru');
            $table->date('kgb_berikutnya_baru');
            $table->enum('sumber', ['auto', 'manual']);
            $table->string('keterangan')->nullable();     
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_kgb_histories');
        Schema::dropIfExists('pegawais');
    }
};