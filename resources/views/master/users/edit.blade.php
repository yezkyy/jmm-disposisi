@extends('layouts.app')

@section('title', 'Edit Pengguna: ' . $user->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="user-cog" class="w-6 h-6 text-primary-600"></i>
                Edit Data Pengguna
            </h1>
            <p class="text-xs text-slate-500 mt-1">Perbarui data profil, peran, divisi, atau status akun pengguna.</p>
        </div>
        <a href="{{ route('master.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
        <form method="POST" action="{{ route('master.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $user->name) }}" 
                    required 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('name') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email', $user->email) }}" 
                    required 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('email') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Role -->
                <div>
                    <label for="role_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Peran Utama (Role) <span class="text-rose-500">*</span>
                    </label>
                    <select id="role_id" name="role_id" required class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('role_id') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @if($user->id === auth()->id())
                        <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                        <p class="mt-1 text-[11px] text-amber-600">Peran akun sendiri tidak dapat diubah demi kepatuhan keamanan.</p>
                    @endif
                    @error('role_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Divisi -->
                <div>
                    <label for="division_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Divisi Kerja
                    </label>
                    <select id="division_id" name="division_id" class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('division_id') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">-- Tanpa Divisi (Khusus Owner/Direksi) --</option>
                        @foreach ($divisions as $div)
                            <option value="{{ $div->id }}" {{ old('division_id', $user->division_id) == $div->id ? 'selected' : '' }}>
                                {{ $div->name }} ({{ $div->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('division_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Password Opsional -->
            <div class="pt-2 border-t border-slate-100">
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Ganti Password (Kosongkan jika tidak ingin mengubah)
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    minlength="8"
                    placeholder="Minimal 8 karakter baru"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('password') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-start sm:items-center gap-3.5 p-3.5 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer select-none group">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="mt-0.5 sm:mt-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-primary-700 transition">Status Akun Aktif</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Jika dinonaktifkan, pengguna tidak dapat masuk ke sistem.</p>
                    </div>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('master.users.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-md shadow-primary-600/30 transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Perbarui Pengguna</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

