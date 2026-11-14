<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ResetPasswordRequest;
use App\Http\Requests\Master\StoreUserRequest;
use App\Http\Requests\Master\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna dengan filter dan pencarian.
     */
    public function index(Request $request): View
    {
        $query = User::with(['role', 'division'])->latest();

        // Pencarian nama atau email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter role
        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        }

        // Filter divisi
        if ($divisionId = $request->input('division_id')) {
            $query->where('division_id', $divisionId);
        }

        // Filter status aktif
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::all();
        $divisions = Division::active()->get();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Pengguna & Hak Akses', 'url' => route('master.users.index')],
        ];

        return view('master.users.index', compact('users', 'roles', 'divisions', 'breadcrumbs'));
    }

    /**
     * Formulir penambahan pengguna baru.
     */
    public function create(): View
    {
        $roles = Role::all();
        $divisions = Division::active()->get();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Pengguna & Hak Akses', 'url' => route('master.users.index')],
            ['label' => 'Tambah Pengguna', 'url' => ''],
        ];

        return view('master.users.create', compact('roles', 'divisions', 'breadcrumbs'));
    }

    /**
     * Simpan pengguna baru ke database.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        AuditLog::record('user_create', $user, [], [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'division_id' => $user->division_id,
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('master.users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    /**
     * Formulir edit pengguna.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        $divisions = Division::all();

        $breadcrumbs = [
            ['label' => 'Master Data', 'url' => ''],
            ['label' => 'Pengguna & Hak Akses', 'url' => route('master.users.index')],
            ['label' => 'Edit Pengguna: ' . $user->name, 'url' => ''],
        ];

        return view('master.users.edit', compact('user', 'roles', 'divisions', 'breadcrumbs'));
    }

    /**
     * Perbarui data pengguna.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $old = $user->only(['name', 'email', 'role_id', 'division_id', 'is_active']);
        $validated = $request->validated();

        // Aturan keamanan: cegah admin menonaktifkan dirinya sendiri bila dia admin terakhir
        if ($user->id === Auth::id() && !$request->boolean('is_active', true)) {
            $otherActiveAdmins = User::where('id', '!=', $user->id)
                ->where('role_id', $user->role_id)
                ->where('is_active', true)
                ->count();

            if ($otherActiveAdmins === 0) {
                return back()->withInput()->with('error', 'Gagal: Anda tidak dapat menonaktifkan akun sendiri karena tidak ada Admin/Finance aktif lainnya.');
            }
        }

        // Aturan keamanan: pengguna tidak boleh mengubah role dirinya sendiri
        if ($user->id === Auth::id() && (int)$validated['role_id'] !== (int)$user->role_id) {
            return back()->withInput()->with('error', 'Gagal: Demi kepatuhan keamanan, Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);

        AuditLog::record('user_update', $user, $old, $user->only(['name', 'email', 'role_id', 'division_id', 'is_active']));

        return redirect()->route('master.users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Ubah status aktif/nonaktif akun pengguna secara cepat.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Cegah admin menonaktifkan dirinya sendiri jika admin terakhir
        if ($user->id === Auth::id() && $user->is_active) {
            $otherActiveAdmins = User::where('id', '!=', $user->id)
                ->where('role_id', $user->role_id)
                ->where('is_active', true)
                ->count();

            if ($otherActiveAdmins === 0) {
                return back()->with('error', 'Gagal: Anda tidak dapat menonaktifkan akun sendiri karena tidak ada Admin/Finance aktif lainnya.');
            }
        }

        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::record('user_toggle_status', $user, ['is_active' => $oldStatus], ['is_active' => $user->is_active]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }

    /**
     * Reset password pengguna oleh administrator berwenang.
     */
    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = Hash::make($request->input('password'));
        $user->save();

        AuditLog::record('password_reset', $user, [], ['reset_by' => Auth::id()]);

        return back()->with('success', "Password untuk pengguna {$user->name} berhasil direset.");
    }
}

