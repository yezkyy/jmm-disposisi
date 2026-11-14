<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingController extends Controller
{
    /**
     * Tampilkan formulir pengaturan identitas perusahaan & sistem.
     */
    public function index(): View
    {
        $settings = [
            'company_name' => CompanySetting::get('company_name', 'PT Jaya Mukti Mandiri'),
            'company_tagline' => CompanySetting::get('company_tagline', 'General Contractor, Heavy Equipment & Logistics Services'),
            'company_address' => CompanySetting::get('company_address', 'Jl. Mulawarman No. 88, Sepinggan, Balikpapan, Kalimantan Timur'),
            'company_phone' => CompanySetting::get('company_phone', '(0542) 765432'),
            'company_email' => CompanySetting::get('company_email', 'finance@jayamuktimandiri.co.id'),
            'disposition_number_format' => CompanySetting::get('disposition_number_format', 'JMM/{YYYY}/{NUMBER}'),
            'disposition_number_digits' => CompanySetting::get('disposition_number_digits', '4'),
        ];

        $breadcrumbs = [
            ['label' => 'Pengaturan & Utilitas', 'url' => ''],
            ['label' => 'Identitas Perusahaan', 'url' => route('master.settings.index')],
        ];

        return view('master.settings.index', compact('settings', 'breadcrumbs'));
    }

    /**
     * Simpan perubahan pengaturan perusahaan.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_tagline' => ['nullable', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:500'],
            'company_phone' => ['required', 'string', 'max:50'],
            'company_email' => ['required', 'email', 'max:255'],
            'disposition_number_format' => ['required', 'string', 'max:50'],
            'disposition_number_digits' => ['required', 'integer', 'min:3', 'max:8'],
        ]);

        $oldValues = [];
        $newValues = [];

        foreach ($validated as $key => $val) {
            $oldValues[$key] = CompanySetting::get($key);
            CompanySetting::set($key, (string)$val);
            $newValues[$key] = (string)$val;
        }

        AuditLog::record('company_settings_updated', null, $oldValues, $newValues);

        return redirect()->route('master.settings.index')
            ->with('success', 'Pengaturan identitas perusahaan dan format nomor disposisi berhasil diperbarui.');
    }
}

