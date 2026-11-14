<?php

namespace App\Helpers;

class Rupiah
{
    /**
     * Format nominal ke format ringkas Rupiah Indonesia:
     * - >= 1 Miliar (1.000.000.000) => Rp X M (Miliar)
     * - >= 1 Juta (1.000.000) => Rp X JT (Juta)
     * - >= 1 Ribu (1.000) => Rp X RB (Ribu)
     * - < 1.000 => Rp X
     */
    public static function short(float|int|null $amount): string
    {
        if ($amount === null || $amount == 0) {
            return 'Rp 0';
        }

        $abs = abs($amount);
        $sign = $amount < 0 ? '-' : '';

        if ($abs >= 1_000_000_000) {
            $val = $abs / 1_000_000_000;
            $formatted = fmod($val, 1) == 0 ? number_format($val, 0, ',', '.') : number_format($val, 1, ',', '.');
            return $sign . 'Rp ' . $formatted . ' M';
        }

        if ($abs >= 1_000_000) {
            $val = $abs / 1_000_000;
            $formatted = fmod($val, 1) == 0 ? number_format($val, 0, ',', '.') : number_format($val, 1, ',', '.');
            return $sign . 'Rp ' . $formatted . ' JT';
        }

        if ($abs >= 1_000) {
            $val = $abs / 1_000;
            $formatted = fmod($val, 1) == 0 ? number_format($val, 0, ',', '.') : number_format($val, 1, ',', '.');
            return $sign . 'Rp ' . $formatted . ' RB';
        }

        return $sign . 'Rp ' . number_format($abs, 0, ',', '.');
    }
}

