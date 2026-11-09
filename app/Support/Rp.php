<?php

namespace App\Support;

/** Format angka untuk tampilan: Rp 1.250.000 dan Rp 1,3 jt. */
final class Rp
{
    public static function fmt($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    public static function juta($n): string
    {
        return 'Rp ' . number_format((float) $n / 1000000, 1, ',', '.') . ' jt';
    }
}
