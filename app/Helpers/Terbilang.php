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
    public static function make(float|int|string|null $number): string
    {
        if (is_null($number)) {
            return 'Nol Rupiah';
        }

        if (is_float($number) || is_int($number)) {
            $val = floor(abs($number));
        } else {
            $raw = trim((string)$number);
            if (is_numeric($raw)) {
                // Standard numeric string (e.g. "67500000.00", "6782000.00", "50000")
                $val = floor(abs((float)$raw));
            } else {
                // Formatted string (e.g. "Rp 67.500.000,00" or "67.500.000")
                $cleaned = preg_replace('/[^\d,\.]/', '', $raw);
                if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
                    // Indonesian format: . is thousands, , is decimal
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } elseif (str_contains($cleaned, '.') && !str_contains($cleaned, ',')) {
                    if (substr_count($cleaned, '.') > 1) {
                        $cleaned = str_replace('.', '', $cleaned);
                    } else {
                        $parts = explode('.', $cleaned);
                        // If 3 digits after dot, e.g. "1.000", it's thousands separator
                        if (strlen($parts[1]) === 3) {
                            $cleaned = str_replace('.', '', $cleaned);
                        }
                    }
                } elseif (str_contains($cleaned, ',') && !str_contains($cleaned, '.')) {
                    $cleaned = str_replace(',', '.', $cleaned);
                }
                $val = floor(abs((float)$cleaned));
            }
        }

        if ($val <= 0) {
            return 'Nol Rupiah';
        }

        return self::convert($val) . ' Rupiah';
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

