<?php

namespace App\Helpers;

class Terbilang
{
    protected static array $words = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
    ];

    /**
     * Konversi angka nominal uang ke kalimat terbilang Bahasa Indonesia.
     */
    public static function make(float|int|string $number): string
    {
        $number = (float) str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)$number);
        $number = floor(abs($number));

        if ($number <= 0) {
            return 'Nol Rupiah';
        }

        return self::convert($number) . ' Rupiah';
    }

    protected static function convert(float $number): string
    {
        if ($number < 12) {
            return self::$words[(int) $number];
        }

        if ($number < 20) {
            return self::convert($number - 10) . ' Belas';
        }

        if ($number < 100) {
            $remainder = $number % 10;
            return self::convert(floor($number / 10)) . ' Puluh' . ($remainder ? ' ' . self::convert($remainder) : '');
        }

        if ($number < 200) {
            return 'Seratus' . ($number - 100 ? ' ' . self::convert($number - 100) : '');
        }

        if ($number < 1000) {
            $remainder = $number % 100;
            return self::convert(floor($number / 100)) . ' Ratus' . ($remainder ? ' ' . self::convert($remainder) : '');
        }

        if ($number < 2000) {
            return 'Seribu' . ($number - 1000 ? ' ' . self::convert($number - 1000) : '');
        }

        if ($number < 1000000) {
            $remainder = $number % 1000;
            return self::convert(floor($number / 1000)) . ' Ribu' . ($remainder ? ' ' . self::convert($remainder) : '');
        }

        if ($number < 1000000000) {
            $remainder = $number % 1000000;
            return self::convert(floor($number / 1000000)) . ' Juta' . ($remainder ? ' ' . self::convert($remainder) : '');
        }

        if ($number < 1000000000000) {
            $remainder = fmod($number, 1000000000);
            return self::convert(floor($number / 1000000000)) . ' Miliar' . ($remainder ? ' ' . self::convert($remainder) : '');
        }

        $remainder = fmod($number, 1000000000000);
        return self::convert(floor($number / 1000000000000)) . ' Triliun' . ($remainder ? ' ' . self::convert($remainder) : '');
    }
}

