<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCategoryRequest;
use App\Http\Requests\Master\UpdateCategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori pengeluaran.
     */
    public function index(Request $request): View
    {
        $query = Category::latest();

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

        $categories = $query->paginate(10)->withQueryString();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Kategori Pengeluaran', 'url' => route('master.categories.index')],
        ];

        return view('master.categories.index', compact('categories', 'breadcrumbs'));
    }

    /**
     * Formulir tambah kategori.
     */
    public function create(): View
    {
        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Kategori Pengeluaran', 'url' => route('master.categories.index')],
            ['label' => 'Tambah Kategori', 'url' => ''],
        ];

        return view('master.categories.create', compact('breadcrumbs'));
    }

    /**
     * Simpan kategori baru.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $category = Category::create($validated);

        AuditLog::record('category_create', $category, [], $category->toArray());

        return redirect()->route('master.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    /**
     * Formulir edit kategori.
     */
    public function edit(Category $category): View
    {
        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Kategori Pengeluaran', 'url' => route('master.categories.index')],
            ['label' => 'Edit Kategori: ' . $category->name, 'url' => ''],
        ];

        return view('master.categories.edit', compact('category', 'breadcrumbs'));
    }

    /**
     * Perbarui data kategori.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->toArray();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        AuditLog::record('category_update', $category, $old, $category->toArray());

        return redirect()->route('master.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/nonaktif kategori.
     */
    public function toggleStatus(Category $category): RedirectResponse
    {
        $oldStatus = $category->is_active;
        $category->is_active = !$category->is_active;
        $category->save();

        AuditLog::record('category_toggle_status', $category, ['is_active' => $oldStatus], ['is_active' => $category->is_active]);

        $statusText = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Kategori '{$category->name}' berhasil {$statusText}.");
    }

    /**
     * Hapus kategori.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Periksa apakah kategori sudah pernah digunakan dalam transaksi di masa depan
        $name = $category->name;
        $old = $category->toArray();
        $category->delete();

        AuditLog::record('category_delete', null, $old, []);

        return redirect()->route('master.categories.index')
            ->with('success', "Kategori '{$name}' berhasil dihapus.");
    }
}

