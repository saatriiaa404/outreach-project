<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amendment D-045: birth place and date are entered separately; age is
     * derived from tanggal_lahir and outreach_date, so it is not stored.
     */
    public function up(): void
    {
        Schema::table('outreaches', function (Blueprint $table) {
            $table->string('tempat_lahir', 100)->nullable()->after('tempat_tgl_lahir_umur');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outreaches', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir']);
        });
    }
};
