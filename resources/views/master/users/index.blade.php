@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Hak Akses')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="users" class="w-6 h-6 text-primary-600"></i>
                Manajemen Pengguna & Hak Akses
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola akun pengguna, penugasan peran utama, divisi, status aktif/nonaktif, dan reset kata sandi.
            </p>
        </div>
        <div>
            <a href="{{ route('master.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm hover:shadow transition">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Pengguna</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search Card -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('master.users.index') }}" x-data="liveFilter({ baseUrl: '{{ route('master.users.index') }}', targetId: 'users-table-card' })" @submit.prevent="submitFilter()" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Keyword -->
            <div>
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Cari Nama / Email</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Ketik kata kunci..." 
                        @input.debounce.350ms="submitFilter()"
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                </div>
            </div>

            <!-- Filter Role -->
            <div>
                <label for="role_id" class="block text-xs font-semibold text-slate-600 mb-1">Peran (Role)</label>
                <select id="role_id" name="role_id" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white">
                    <option value="">Semua Peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Division -->
            <div>
                <label for="division_id" class="block text-xs font-semibold text-slate-600 mb-1">Divisi</label>
                <select id="division_id" name="division_id" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white">
                    <option value="">Semua Divisi</option>
                    @foreach ($divisions as $div)
                        <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                            {{ $div->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status & Submit -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status Akun</label>
                    <select id="status" name="status" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-xs rounded-xl transition h-[38px] flex items-center gap-1.5" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset</span>
                </button>
                <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center pb-2">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                </span>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div id="users-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[11px] font-semibold">
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Divisi</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Terdaftar</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('master.users.edit', $user) }}'">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $roleBadge = match($user->role?->name) {
                                        'admin_finance' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'manager' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'owner' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $roleBadge }}">
                                    {{ $user->role?->display_name ?? 'Tanpa Peran' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($user->division)
                                    <span class="inline-flex items-center gap-1 font-medium text-slate-800">
                                        <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $user->division->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right" onclick="event.stopPropagation()">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('master.users.edit', $user) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 transition" title="Edit Pengguna">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>

                                    <button type="button" onclick="openResetModal({{ $user->id }}, '{{ addslashes($user->name) }}')" class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-slate-100 transition" title="Reset Password">
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </button>

                                    <form method="POST" action="{{ route('master.users.toggle-status', $user) }}" class="inline" id="toggle-form-{{ $user->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" onclick="confirmToggleStatus({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->is_active ? 'true' : 'false' }})" class="p-1.5 rounded-lg transition {{ $user->is_active ? 'text-slate-600 hover:text-rose-600 hover:bg-rose-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-emerald-50' }}" title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                            <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i data-lucide="user-x" class="w-8 h-8 text-slate-300"></i>
                                    <p class="font-medium text-slate-500">Tidak ada data pengguna yang sesuai dengan filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Reset Password -->
<div id="reset-password-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="key" class="w-4 h-4 text-amber-600"></i>
                Reset Password Pengguna
            </h3>
            <button type="button" onclick="closeResetModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="reset-password-form" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <p class="text-xs text-slate-500 mb-3">
                    Pengguna: <strong id="reset-user-name" class="text-slate-800"></strong>
                </p>
                <label for="modal-password" class="block text-xs font-semibold text-slate-700 mb-1">Password Baru</label>
                <input 
                    type="password" 
                    id="modal-password" 
                    name="password" 
                    required 
                    minlength="8"
                    placeholder="Minimal 8 karakter"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
            </div>
            <div>
                <label for="modal-password-confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                <input 
                    type="password" 
                    id="modal-password-confirmation" 
                    name="password_confirmation" 
                    required 
                    minlength="8"
                    placeholder="Ulangi password baru"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeResetModal()" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white shadow-sm">
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmToggleStatus(userId, userName, isActive) {
        const actionText = isActive ? 'menonaktifkan' : 'mengaktifkan';
        Swal.fire({
            title: `Konfirmasi ${isActive ? 'Nonaktifkan' : 'Aktifkan'}?`,
            text: `Apakah Anda yakin ingin ${actionText} akun "${userName}"?`,
            icon: isActive ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: `Ya, ${isActive ? 'Nonaktifkan' : 'Aktifkan'}`,
            cancelButtonText: 'Batal',
            confirmButtonColor: isActive ? '#e11d48' : '#059669',
            cancelButtonColor: '#64748b',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2',
                cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`toggle-form-${userId}`).submit();
            }
        });
    }

    function openResetModal(userId, userName) {
        document.getElementById('reset-user-name').innerText = userName;
        document.getElementById('reset-password-form').action = `/master/users/${userId}/reset-password`;
        document.getElementById('reset-password-modal').classList.remove('hidden');
    }

    function closeResetModal() {
        document.getElementById('reset-password-modal').classList.add('hidden');
        document.getElementById('modal-password').value = '';
        document.getElementById('modal-password-confirmation').value = '';
    }
</script>
@endpush

