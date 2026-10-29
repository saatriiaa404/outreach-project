<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amendment D-067: an optional profile per account (photo, phone number,
     * home address); the full name stays in `name`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('photo_file_id')->nullable()->after('pangkat')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->string('phone', 20)->nullable()->after('photo_file_id');
            $table->string('address', 500)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_file_id');
            $table->dropColumn(['phone', 'address']);
        });
    }
};
