<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name' => 'PT Jaya Mukti Mandiri',
            'company_tagline' => 'General Contractor, Heavy Equipment & Logistics Services',
            'company_address' => 'Jl. Mulawarman No. 88, Sepinggan, Balikpapan, Kalimantan Timur',
            'company_phone' => '(0542) 765432',
            'company_email' => 'finance@jayamuktimandiri.co.id',
            'disposition_number_format' => 'JMM/{YYYY}/{NUMBER}',
            'disposition_number_digits' => '4',
        ];

        foreach ($settings as $key => $val) {
            CompanySetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val]
            );
        }
    }
}

