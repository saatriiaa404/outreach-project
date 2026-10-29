<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * Run the migrations.
     *
     * Decision D-046: the combined column is replaced by tempat_lahir and
     * tanggal_lahir (D-045). Existing text such as "Tuban, 15 Juni 2006 / 20" is
     * moved to the new columns first; the age part is dropped because it is
     * derived. Parts that cannot be read are left empty rather than guessed.
     */
    public function up(): void
    {
        DB::table('outreaches')
            ->whereNotNull('tempat_tgl_lahir_umur')
            ->whereNull('tempat_lahir')
            ->whereNull('tanggal_lahir')
            ->orderBy('id')
            ->each(function (object $row) {
                [$place, $date] = $this->parse($row->tempat_tgl_lahir_umur);

                DB::table('outreaches')->where('id', $row->id)->update([
                    'tempat_lahir' => $place,
                    'tanggal_lahir' => $date,
                ]);
            });

        Schema::table('outreaches', function (Blueprint $table) {
            $table->dropColumn('tempat_tgl_lahir_umur');
        });
    }

    /**
     * Reverse the migrations. Moved values are not copied back.
     */
    public function down(): void
    {
        Schema::table('outreaches', function (Blueprint $table) {
            $table->string('tempat_tgl_lahir_umur')->nullable()->after('nik');
        });
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function parse(string $value): array
    {
        $beforeAge = trim(explode('/', $value, 2)[0]);
        $parts = array_map('trim', explode(',', $beforeAge, 2));
        $place = $parts[0] !== '' ? mb_substr($parts[0], 0, 100) : null;
        $date = null;

        if (isset($parts[1]) && preg_match('/^(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})$/u', $parts[1], $m)) {
            $month = self::MONTHS[mb_strtolower($m[2])] ?? null;

            if ($month && checkdate($month, (int) $m[1], (int) $m[3])) {
                $date = sprintf('%04d-%02d-%02d', $m[3], $month, $m[1]);
            }
        }

        return [$place, $date];
    }
};
