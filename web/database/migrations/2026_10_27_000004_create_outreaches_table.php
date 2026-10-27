<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 section 5.1. Form fields are nullable because a Draft
     * may be incomplete; Finalisasi validation enforces required fields.
     */
    public function up(): void
    {
        Schema::create('outreaches', function (Blueprint $table) {
            $table->id();
            $table->string('draft_code', 30)->unique();
            $table->string('outreach_number', 30)->nullable()->unique();
            $table->foreignId('source_id')->constrained('outreach_sources')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('type_id')->constrained('outreach_types')->restrictOnDelete()->restrictOnUpdate();
            $table->date('outreach_date');
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();

            // Identity / client snapshot
            $table->string('nama')->nullable();
            $table->string('nik', 32)->nullable();
            $table->string('tempat_tgl_lahir_umur')->nullable();
            $table->string('agama', 100)->nullable();
            $table->text('alamat_ktp')->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->string('status_kependudukan', 3)->nullable();
            $table->string('tahun_warga_surabaya', 50)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('status_hubungan_keluarga', 100)->nullable();
            $table->unsignedSmallInteger('jumlah_anggota_kk')->nullable();
            $table->unsignedSmallInteger('jumlah_kk_dalam_rumah')->nullable();
            $table->string('status_tempat_tinggal', 100)->nullable();
            $table->unsignedBigInteger('penghasilan_klien')->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->string('status_perkawinan', 100)->nullable();
            $table->string('pendidikan_terakhir', 100)->nullable();
            $table->string('status_kesejahteraan', 50)->nullable();
            $table->unsignedTinyInteger('desil_1_10')->nullable();
            $table->string('jaminan_kesehatan')->nullable();
            $table->boolean('merokok')->nullable();
            $table->boolean('bantuan_pernah_diterima')->nullable();
            $table->text('bantuan_detail')->nullable();
            $table->boolean('punya_hutang')->nullable();
            $table->unsignedBigInteger('besar_hutang')->nullable();

            // Family income
            $table->unsignedBigInteger('penghasilan_kepala_keluarga')->nullable();
            $table->unsignedBigInteger('penghasilan_suami_istri')->nullable();
            $table->unsignedBigInteger('penghasilan_anak_1kk')->nullable();
            $table->unsignedBigInteger('penghasilan_lainnya')->nullable();

            // 13 expense fields
            foreach ([
                'sewa', 'belanja_memasak', 'air_minum', 'lpg_pgn', 'listrik', 'pdam', 'sekolah',
                'internet', 'pulsa', 'rokok', 'berobat', 'angsuran', 'bbm',
            ] as $expense) {
                $table->unsignedBigInteger('pengeluaran_'.$expense)->nullable();
            }

            // House / assets
            $table->string('luas_bangunan', 100)->nullable();
            $table->string('jenis_lantai', 100)->nullable();
            $table->string('jenis_dinding', 100)->nullable();
            $table->string('jenis_atap', 100)->nullable();
            $table->string('daya_listrik', 100)->nullable();
            $table->string('sumber_air', 100)->nullable();
            $table->string('mck_layak', 20)->nullable();
            foreach (['sepeda_angin', 'sepeda_listrik', 'becak', 'sepeda_motor', 'roda_4', 'kendaraan_lainnya'] as $vehicle) {
                $table->unsignedSmallInteger('jumlah_'.$vehicle)->nullable();
            }
            $table->string('nama_kendaraan_lainnya')->nullable();
            $table->text('perhiasan')->nullable();
            $table->text('aset_lainnya')->nullable();

            // Narrative
            $table->text('upaya_dilakukan')->nullable();
            $table->text('harapan_klien')->nullable();
            $table->text('uraian_alasan_intervensi')->nullable();

            // State / concurrency
            $table->string('status', 25)->default('DRAFT');
            $table->unsignedInteger('edit_version')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('outreach_date');
            $table->index('nik');
            $table->index(['status', 'petugas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreaches');
    }
};
