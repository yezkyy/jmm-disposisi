<?php

namespace App\Http\Controllers\Disposition;

use App\Helpers\Terbilang;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Disposition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DispositionDocumentController extends Controller
{
    /**
     * Generate berkas formal PDF A4 Disposisi Pengeluaran.
     */
    public function pdf(Disposition $disposition): Response
    {
        $disposition->load([
            'applicant',
            'division',
            'items.category',
            'recipients',
            'approvals.actor',
        ]);

        $companyName = CompanySetting::get('company_name', 'PT Jaya Mukti Mandiri');
        $companyTagline = CompanySetting::get('company_tagline', 'General Contractor, Heavy Equipment & Logistics Services');
        $companyAddress = CompanySetting::get('company_address', 'Jl. Mulawarman No. 88, Sepinggan, Balikpapan');
        $companyPhone = CompanySetting::get('company_phone', '(0542) 765432');
        $companyEmail = CompanySetting::get('company_email', 'finance@jayamuktimandiri.co.id');

        // Encode logo ke base64 agar dapat di-render DomPDF secara reliabel
        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $effectiveTotal = $disposition->final_approved_total ?? $disposition->requested_total;
        $terbilang = Terbilang::make($effectiveTotal);

        $pdf = Pdf::loadView('dispositions.document-pdf', compact(
            'disposition',
            'companyName',
            'companyTagline',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'logoBase64',
            'terbilang'
        ))->setPaper('a4', 'portrait');

        $safeNum = str_replace('/', '_', $disposition->disposition_number ?: 'Draft_' . $disposition->id);
        $filename = "Disposisi_{$safeNum}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Tampilan cetak langsung browser (HTML Print).
     */
    public function print(Disposition $disposition): View
    {
        $disposition->load([
            'applicant',
            'division',
            'items.category',
            'recipients',
            'approvals.actor',
        ]);

        $companyName = CompanySetting::get('company_name', 'PT Jaya Mukti Mandiri');
        $companyTagline = CompanySetting::get('company_tagline', 'General Contractor, Heavy Equipment & Logistics Services');
        $companyAddress = CompanySetting::get('company_address', 'Jl. Mulawarman No. 88, Sepinggan, Balikpapan');
        $companyPhone = CompanySetting::get('company_phone', '(0542) 765432');
        $companyEmail = CompanySetting::get('company_email', 'finance@jayamuktimandiri.co.id');

        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $effectiveTotal = $disposition->final_approved_total ?? $disposition->requested_total;
        $terbilang = Terbilang::make($effectiveTotal);

        return view('dispositions.document-pdf', compact(
            'disposition',
            'companyName',
            'companyTagline',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'logoBase64',
            'terbilang'
        ));
    }

    /**
     * Tampilan dokumen format Gambar / JPG untuk kemudahan sharing via WhatsApp ke Owner.
     */
    public function image(Disposition $disposition): View
    {
        $disposition->load([
            'applicant',
            'division',
            'items',
            'recipients',
            'approvals.actor',
        ]);

        $companyName = CompanySetting::get('company_name', 'PT Jaya Mukti Mandiri');
        $companyTagline = CompanySetting::get('company_tagline', 'General Contractor, Heavy Equipment & Logistics Services');
        $companyAddress = CompanySetting::get('company_address', 'Jl. Mulawarman No. 88, Sepinggan, Balikpapan');
        $companyPhone = CompanySetting::get('company_phone', '(0542) 765432');
        $companyEmail = CompanySetting::get('company_email', 'finance@jayamuktimandiri.co.id');

        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $effectiveTotal = $disposition->final_approved_total ?? $disposition->requested_total;
        $terbilang = Terbilang::make($effectiveTotal);

        return view('dispositions.document-image', compact(
            'disposition',
            'companyName',
            'companyTagline',
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'logoBase64',
            'terbilang'
        ));
    }
}

