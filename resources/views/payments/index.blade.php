@extends('layouts.app')

@section('title', 'Riwayat Transaksi Pembayaran')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="receipt" class="w-6 h-6 text-primary-600"></i>
                Riwayat Transaksi Pembayaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Catatan pencairan dana, voucher kas/bank, dan bukti transfer pembayaran disposisi.
            </p>
        </div>

        @if(auth()->user()->isAdminFinance() || auth()->user()->isOwner())
            <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition shadow-md shadow-primary-600/20">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Catat Pembayaran Baru</span>
            </a>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('reports.index') }}" class="group bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-300 transition flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors block mb-1">Total Dana Dicairkan (Posted)</span>
                <span class="text-xl font-extrabold text-emerald-600 font-mono">
                    Rp {{ number_format($totalPaidSum, 2, ',', '.') }}
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition flex items-center justify-center">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
        </a>

        <a href="{{ route('payments.index') }}" class="group bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-primary-300 transition flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-primary-600 transition-colors block mb-1">Total Transaksi Tercatat</span>
                <span class="text-xl font-extrabold text-slate-800 font-mono">
                    {{ $payments->total() }} Voucher
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition flex items-center justify-center">
                <i data-lucide="file-check" class="w-5 h-5"></i>
            </div>
        </a>

        <a href="{{ route('dispositions.index', ['payment_status' => 'unpaid']) }}" class="group bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-amber-300 transition flex items-center justify-between cursor-pointer">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-amber-600 transition-colors block mb-1">Status Disposisi Siap Bayar</span>
                <span class="text-xs font-bold text-amber-600 group-hover:text-amber-700 flex items-center gap-1 mt-1">
                    <span>Lihat Antrean Belum Lunas</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                </span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </a>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('payments.index') }}" x-data="liveFilter({ baseUrl: '{{ route('payments.index') }}', targetId: 'payments-table-card' })" @submit.prevent="submitFilter()" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pencarian</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="No. voucher, disposisi, penerima..." 
                        @input.debounce.350ms="submitFilter()"
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                </div>
            </div>

            <!-- Metode -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Metode</label>
                <select name="method" @change="submitFilter()" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                    <option value="">Semua Metode</option>
                    <option value="transfer" {{ request('method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="other" {{ request('method') === 'other' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                <select name="status" @change="submitFilter()" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white">
                    <option value="">Semua Status</option>
                    <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted (Berhasil)</option>
                    <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>Reversed (Dibatalkan)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="button" @click="resetFilter()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset</span>
                </button>
                <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center gap-1 pb-1">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                </span>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div id="payments-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">No. Voucher</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Disposisi</th>
                        <th class="py-3 px-4">Penerima & Rekening</th>
                        <th class="py-3 px-4">Rekening Sumber</th>
                        <th class="py-3 px-4 text-right">Nominal (Rp)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($payments as $pay)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('payments.show', $pay) }}'">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                <span class="text-primary-600 hover:text-primary-700">
                                    {{ $pay->payment_number }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <span class="font-semibold text-slate-800 hover:text-primary-600 truncate block">
                                    {{ $pay->disposition?->disposition_number }}
                                </span>
                                <p class="text-[10px] text-slate-400 truncate">{{ $pay->disposition?->subject }}</p>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800">{{ $pay->recipient?->recipient_name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $pay->recipient?->primary_bank_name }} - {{ $pay->recipient?->primary_account_number }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($pay->sourceAccount)
                                    <div class="font-medium text-slate-700">{{ $pay->sourceAccount->bank_name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $pay->sourceAccount->account_number }}</div>
                                @else
                                    <span class="text-slate-400 uppercase text-[11px]">{{ $pay->method_label }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                <span class="{{ $pay->status === 'reversed' ? 'line-through text-slate-400' : 'text-emerald-700' }}">
                                    Rp {{ number_format($pay->amount, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                    @if($pay->status === 'posted') bg-emerald-100 text-emerald-800
                                    @elseif($pay->status === 'reversed') bg-rose-100 text-rose-800
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ strtoupper($pay->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('payments.show', $pay) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="Lihat Voucher">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 inline"></i>
                                    </a>
                                    @if ($pay->proof_path)
                                        <a href="{{ asset('storage/' . $pay->proof_path) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold transition" title="Lihat Bukti Transfer">
                                            <i data-lucide="file" class="w-3.5 h-3.5 inline"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="receipt" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Belum ada riwayat transaksi pembayaran</p>
                                <p class="text-xs text-slate-400 mt-1">Transaksi yang dicatat akan muncul pada daftar ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

