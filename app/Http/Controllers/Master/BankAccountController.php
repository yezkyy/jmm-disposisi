<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreBankAccountRequest;
use App\Http\Requests\Master\UpdateBankAccountRequest;
use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    /**
     * Tampilkan daftar master rekening.
     */
    public function index(Request $request): View
    {
        $query = BankAccount::with(['bank', 'creator'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('owner_reference', 'like', "%{$search}%");
            });
        }

        if ($ownerType = $request->input('owner_type')) {
            $query->where('owner_type', $ownerType);
        }

        if ($bankId = $request->input('bank_id')) {
            $query->where('bank_id', $bankId);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $accounts = $query->paginate(10)->withQueryString();
        $banks = Bank::active()->get();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Master Rekening', 'url' => route('master.bank-accounts.index')],
        ];

        return view('master.bank-accounts.index', compact('accounts', 'banks', 'breadcrumbs'));
    }

    /**
     * Formulir tambah rekening.
     */
    public function create(): View
    {
        $banks = Bank::active()->get();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Master Rekening', 'url' => route('master.bank-accounts.index')],
            ['label' => 'Tambah Rekening', 'url' => ''],
        ];

        return view('master.bank-accounts.create', compact('banks', 'breadcrumbs'));
    }

    /**
     * Simpan rekening baru.
     */
    public function store(StoreBankAccountRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['account_number'] = (string) $validated['account_number']; // Jaga leading zeros
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = Auth::id();

        // Jika bank_id dipilih, pastikan bank_name sesuai
        if (!empty($validated['bank_id'])) {
            $bank = Bank::find($validated['bank_id']);
            if ($bank) {
                $validated['bank_name'] = $bank->name;
            }
        }

        $account = BankAccount::create($validated);

        AuditLog::record('bank_account_create', $account, [], $account->toArray());

        // Jika request via AJAX / JSON (untuk persiapan modal form disposisi tanpa reload)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Rekening '{$account->owner_name}' ({$account->bank_name} - {$account->account_number}) berhasil ditambahkan.",
                'data' => $account,
            ]);
        }

        return redirect()->route('master.bank-accounts.index')
            ->with('success', "Rekening atas nama '{$account->owner_name}' berhasil ditambahkan.");
    }

    /**
     * Formulir edit rekening.
     */
    public function edit(BankAccount $bankAccount): View
    {
        $banks = Bank::all();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Master Rekening', 'url' => route('master.bank-accounts.index')],
            ['label' => 'Edit Rekening: ' . $bankAccount->owner_name, 'url' => ''],
        ];

        return view('master.bank-accounts.edit', compact('bankAccount', 'banks', 'breadcrumbs'));
    }

    /**
     * Perbarui rekening.
     */
    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $old = $bankAccount->toArray();
        $validated = $request->validated();
        $validated['account_number'] = (string) $validated['account_number']; // Jaga leading zeros
        $validated['is_active'] = $request->boolean('is_active', true);

        if (!empty($validated['bank_id'])) {
            $bank = Bank::find($validated['bank_id']);
            if ($bank) {
                $validated['bank_name'] = $bank->name;
            }
        }

        $bankAccount->update($validated);

        AuditLog::record('bank_account_update', $bankAccount, $old, $bankAccount->toArray());

        return redirect()->route('master.bank-accounts.index')
            ->with('success', "Data rekening atas nama '{$bankAccount->owner_name}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/nonaktif rekening.
     */
    public function toggleStatus(BankAccount $bankAccount): RedirectResponse
    {
        $oldStatus = $bankAccount->is_active;
        $bankAccount->is_active = !$bankAccount->is_active;
        $bankAccount->save();

        AuditLog::record('bank_account_toggle_status', $bankAccount, ['is_active' => $oldStatus], ['is_active' => $bankAccount->is_active]);

        $statusText = $bankAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Rekening '{$bankAccount->owner_name}' berhasil {$statusText}.");
    }

    /**
     * Hapus rekening bila belum pernah ada transaksi terkait.
     */
    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $name = $bankAccount->owner_name;
        $old = $bankAccount->toArray();
        $bankAccount->delete();

        AuditLog::record('bank_account_delete', null, $old, []);

        return redirect()->route('master.bank-accounts.index')
            ->with('success', "Rekening atas nama '{$name}' berhasil dihapus.");
    }
}

