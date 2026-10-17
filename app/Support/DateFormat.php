<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Pembantu format tanggal sesuai preferensi proyek (mm/dd/yyyy atau dd/mm/yyyy).
 */
class DateFormat
{
    public static function format(?string $date, string $format = 'mm/dd/yyyy'): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = Carbon::parse($date);

        return $format === 'dd/mm/yyyy'
            ? $carbon->format('d/m/Y')
            : $carbon->format('m/d/Y');
    }
}
