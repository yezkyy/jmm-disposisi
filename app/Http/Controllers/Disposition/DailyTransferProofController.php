<?php

namespace App\Http\Controllers\Disposition;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailyTransferProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyTransferProofController extends Controller
{
    /**
     * Simpan bukti transfer harian baru (format JPG/JPEG/PNG).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:10240'],
        ], [
            'transfer_date.required' => 'Tanggal transfer wajib diisi.',
            'file.required' => 'File bukti transfer wajib diunggah.',
            'file.image' => 'File harus berupa gambar.',
            'file.mimes' => 'Format gambar harus JPEG atau JPG atau PNG.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $filePath = $file->store('transfer-proofs/daily', 'public');

        $proof = DailyTransferProof::create([
            'transfer_date' => $validated['transfer_date'],
            'title' => $validated['title'] ?: 'Bukti Transfer Tanggal ' . date('d/m/Y', strtotime($validated['transfer_date'])),
            'notes' => $validated['notes'],
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'uploaded_by' => Auth::id(),
        ]);

        AuditLog::record(
            'upload_daily_transfer_proof',
            $proof,
            [],
            $proof->toArray()
        );

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diunggah',
            'message' => 'Bukti transfer harian berhasil disimpan.',
        ]);
    }

    /**
     * Unduh file bukti transfer harian.
     */
    public function download(DailyTransferProof $proof): BinaryFileResponse
    {
        $path = Storage::disk('public')->path($proof->file_path);
        if (!file_exists($path)) {
            abort(404, 'File bukti transfer tidak ditemukan di server.');
        }

        return response()->download($path, $proof->file_name);
    }

    /**
     * Hapus bukti transfer harian.
     */
    public function destroy(DailyTransferProof $proof): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdminFinance() && !$user->isOwner()) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus bukti transfer ini.');
        }

        // Hapus fisik file dari disk
        if (Storage::disk('public')->exists($proof->file_path)) {
            Storage::disk('public')->delete($proof->file_path);
        }

        $oldData = $proof->toArray();
        $proof->delete();

        AuditLog::record(
            'delete_daily_transfer_proof',
            $proof,
            $oldData,
            []
        );

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'message' => 'Bukti transfer harian berhasil dihapus.',
        ]);
    }
}
