<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDivisionRequest;
use App\Http\Requests\Master\UpdateDivisionRequest;
use App\Models\AuditLog;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisionController extends Controller
{
    /**
     * Tampilkan daftar master divisi.
     */
    public function index(Request $request): View
    {
        $query = Division::withCount('users')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $divisions = $query->paginate(10)->withQueryString();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Divisi', 'url' => route('master.divisions.index')],
        ];

        return view('master.divisions.index', compact('divisions', 'breadcrumbs'));
    }

    /**
     * Formulir tambah divisi.
     */
    public function create(): View
    {
        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Divisi', 'url' => route('master.divisions.index')],
            ['label' => 'Tambah Divisi', 'url' => ''],
        ];

        return view('master.divisions.create', compact('breadcrumbs'));
    }

    /**
     * Simpan divisi baru.
     */
    public function store(StoreDivisionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $division = Division::create($validated);

        AuditLog::record('division_create', $division, [], $division->toArray());

        return redirect()->route('master.divisions.index')
            ->with('success', "Divisi {$division->name} berhasil ditambahkan.");
    }

    /**
     * Formulir edit divisi.
     */
    public function edit(Division $division): View
    {
        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Divisi', 'url' => route('master.divisions.index')],
            ['label' => 'Edit Divisi: ' . $division->name, 'url' => ''],
        ];

        return view('master.divisions.edit', compact('division', 'breadcrumbs'));
    }

    /**
     * Perbarui divisi.
     */
    public function update(UpdateDivisionRequest $request, Division $division): RedirectResponse
    {
        $old = $division->toArray();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $division->update($validated);

        AuditLog::record('division_update', $division, $old, $division->toArray());

        return redirect()->route('master.divisions.index')
            ->with('success', "Divisi {$division->name} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/nonaktif divisi.
     */
    public function toggleStatus(Division $division): RedirectResponse
    {
        $oldStatus = $division->is_active;
        $division->is_active = !$division->is_active;
        $division->save();

        AuditLog::record('division_toggle_status', $division, ['is_active' => $oldStatus], ['is_active' => $division->is_active]);

        $statusText = $division->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Divisi {$division->name} berhasil {$statusText}.");
    }

    /**
     * Hapus divisi dengan pengecekan integritas relasi.
     */
    public function destroy(Division $division): RedirectResponse
    {
        if ($division->users()->exists()) {
            return back()->with('error', "Gagal: Divisi {$division->name} masih memiliki anggota pengguna. Nonaktifkan status divisi alih-alih menghapusnya.");
        }

        $name = $division->name;
        $old = $division->toArray();
        $division->delete();

        AuditLog::record('division_delete', null, $old, []);

        return redirect()->route('master.divisions.index')
            ->with('success', "Divisi {$name} berhasil dihapus.");
    }
}

