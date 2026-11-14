<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DispositionSequence extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'last_number'];

    /**
     * Dapatkan nomor urut berikutnya secara thread-safe menggunakan lockForUpdate.
     */
    public static function getNextNumber(int $year): int
    {
        return DB::transaction(function () use ($year) {
            $seq = self::where('year', $year)->lockForUpdate()->first();

            if (!$seq) {
                $seq = self::create([
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $seq->last_number += 1;
            $seq->save();

            return $seq->last_number;
        });
    }

    /**
     * Format nomor disposisi berdasarkan pengaturan perusahaan.
     * Contoh default: JMM/2026/0001
     */
    public static function generateDispositionNumber(int $year): string
    {
        $nextNumber = self::getNextNumber($year);
        $format = CompanySetting::get('disposition_number_format', 'JMM/{YYYY}/{NUMBER}');
        $digits = (int) CompanySetting::get('disposition_number_digits', '4');

        $paddedNumber = str_pad((string)$nextNumber, $digits, '0', STR_PAD_LEFT);

        return str_replace(['{YYYY}', '{NUMBER}'], [$year, $paddedNumber], $format);
    }
}

