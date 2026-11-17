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

            // Hitung nomor tertinggi yang sudah ada di tabel dispositions
            $maxExisting = self::getMaxExistingNumberForYear($year);
            $current = max($seq->last_number, $maxExisting);
            $current += 1;

            $seq->last_number = $current;
            $seq->save();

            return $seq->last_number;
        });
    }

    /**
     * Format nomor disposisi berdasarkan pengaturan perusahaan dan cegah duplikasi.
     * Contoh default: JMM/2026/0001
     */
    public static function generateDispositionNumber(int $year): string
    {
        return DB::transaction(function () use ($year) {
            $format = CompanySetting::get('disposition_number_format', 'JMM/{YYYY}/{NUMBER}');
            $digits = (int) CompanySetting::get('disposition_number_digits', '4');

            $seq = self::where('year', $year)->lockForUpdate()->first();

            if (!$seq) {
                $seq = self::create([
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            // Cari nomor tertinggi yang sudah ada di database dispositions untuk tahun ini
            $maxExisting = self::getMaxExistingNumberForYear($year);
            $currentNumber = max($seq->last_number, $maxExisting);

            // Cari nomor kandidat yang benar-benar belum pernah dipakai di tabel dispositions
            do {
                $currentNumber++;
                $paddedNumber = str_pad((string)$currentNumber, $digits, '0', STR_PAD_LEFT);
                $candidate = str_replace(['{YYYY}', '{NUMBER}'], [$year, $paddedNumber], $format);
            } while (Disposition::where('disposition_number', $candidate)->exists());

            $seq->last_number = $currentNumber;
            $seq->save();

            return $candidate;
        });
    }

    /**
     * Dapatkan angka urutan tertinggi yang sudah tercatat di tabel dispositions untuk tahun yang bersangkutan.
     */
    public static function getMaxExistingNumberForYear(int $year): int
    {
        $existingNumbers = Disposition::where(function ($q) use ($year) {
            $q->where('disposition_number', 'like', "%/{$year}/%")
              ->orWhere('disposition_number', 'like', "%{$year}%");
        })->pluck('disposition_number');

        $max = 0;
        foreach ($existingNumbers as $num) {
            if (empty($num)) {
                continue;
            }
            if (preg_match('/(\d+)$/', $num, $matches)) {
                $val = (int) $matches[1];
                if ($val > $max) {
                    $max = $val;
                }
            }
        }

        return $max;
    }
}

