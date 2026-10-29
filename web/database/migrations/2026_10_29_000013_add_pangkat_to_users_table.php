<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amendment D-065: the Kepala's rank (pangkat) is printed in the approval
     * block under the name, like the NIP of D-041.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pangkat', 100)->nullable()->after('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pangkat');
        });
    }
};
