<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Quick filters picked from a table's column header (D-072), like the filter
 * chips of a file manager: a date range relative to today, a Source and a
 * Jenis. They narrow what the user may already see; visibility (D-039) is
 * applied before them. Status stays with each page's own status filter.
 */
class OutreachColumnFilters
{
    public const DATE_RANGES = [
        'hari-ini' => 'Hari ini',
        '7-hari' => '7 hari terakhir',
        '30-hari' => '30 hari terakhir',
        'bulan-ini' => 'Bulan ini',
        'tahun-ini' => 'Tahun ini',
    ];

    /**
     * Kelurahan local time; "today" follows the Petugas' calendar.
     */
    private const TIMEZONE = 'Asia/Jakarta';

    /**
     * The valid filters in the request; anything unknown is ignored.
     *
     * @return array{tanggal: string|null, source: int|null, type: int|null}
     */
    public static function from(Request $request): array
    {
        $range = $request->string('tanggal')->toString();
        $id = fn (string $key) => ctype_digit($request->string($key)->toString()) ? (int) $request->string($key)->toString() : null;

        return [
            'tanggal' => isset(self::DATE_RANGES[$range]) ? $range : null,
            'source' => $id('source'),
            'type' => $id('type'),
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{tanggal: string|null, source: int|null, type: int|null}  $filters
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['tanggal'], function (Builder $query, string $range) {
                [$from, $to] = self::range($range);
                $query->whereBetween('outreach_date', [$from, $to]);
            })
            ->when($filters['source'], fn (Builder $query, int $id) => $query->where('source_id', $id))
            ->when($filters['type'], fn (Builder $query, int $id) => $query->where('type_id', $id));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function range(string $range): array
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        $from = match ($range) {
            'hari-ini' => $today,
            '7-hari' => $today->subDays(6),
            '30-hari' => $today->subDays(29),
            'bulan-ini' => $today->startOfMonth(),
            default => $today->startOfYear(),
        };

        return [$from->toDateString(), $today->toDateString()];
    }
}
