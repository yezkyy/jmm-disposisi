<!-- Sidebar Backdrop (Mobile) -->
<div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm transition-opacity hidden md:hidden"></div>

<!-- Sidebar Container -->
<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 shadow-xl md:shadow-none">
    <!-- Brand Header -->
    <div class="h-20 min-h-[5rem] max-h-[5rem] flex-shrink-0 flex items-center gap-3 px-5 border-b border-slate-800 bg-slate-950/50">
        <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center flex-shrink-0 shadow-sm">
            <img src="{{ asset('images/logo.png') }}" alt="Logo PT Jaya Mukti Mandiri" class="w-full h-full object-contain">
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xs font-bold uppercase tracking-wider text-white truncate">JMM Disposisi</h1>
            <p class="text-[10px] text-slate-400 truncate">PT Jaya Mukti Mandiri</p>
        </div>
        <button type="button" id="mobile-sidebar-close" class="md:hidden text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        <!-- Main Nav -->
        <div>
            <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Utama</div>
            <ul class="space-y-1 text-xs">
                <li>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'text-white bg-primary-600/90 shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Master Data -->
        @auth
            <div>
                <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Master Data</div>
                <ul class="space-y-1 text-xs">
                    <li>
                        <a href="{{ route('master.bank-accounts.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.bank-accounts.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i data-lucide="wallet" class="w-4 h-4 {{ request()->routeIs('master.bank-accounts.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                <span>Master Rekening</span>
                            </span>
                        </a>
                    </li>

                    @if(Auth::user()->isAdminFinance())
                        <li>
                            <a href="{{ route('master.categories.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.categories.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="tags" class="w-4 h-4 {{ request()->routeIs('master.categories.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Kategori Pengeluaran</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('master.divisions.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.divisions.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="building-2" class="w-4 h-4 {{ request()->routeIs('master.divisions.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Divisi Perusahaan</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('master.users.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.users.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('master.users.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Pengguna & Hak Akses</span>
                                </span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            @if(Auth::user()->isAdminFinance() || Auth::user()->isOwner())
                <div>
                    <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Pengaturan & Keamanan</div>
                    <ul class="space-y-1 text-xs">
                        <li>
                            <a href="{{ route('master.settings.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.settings.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="settings" class="w-4 h-4 {{ request()->routeIs('master.settings.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Identitas Perusahaan</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('master.audit.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.audit.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="shield-alert" class="w-4 h-4 {{ request()->routeIs('master.audit.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Jejak Audit</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('master.backups.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('master.backups.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="database-backup" class="w-4 h-4 {{ request()->routeIs('master.backups.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Backup & Pemulihan</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        @endauth

        <!-- Modul Disposisi (Tahap 3 & Lanjutan) -->
        @auth
            <div>
                <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Disposisi & Operasional</div>
                <ul class="space-y-1 text-xs">
                    <li>
                        <a href="{{ route('dispositions.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('dispositions.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i data-lucide="file-text" class="w-4 h-4 {{ request()->routeIs('dispositions.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                <span>Pengajuan Disposisi</span>
                            </span>
                        </a>
                    </li>
                    @if(Auth::user()->isManager() || Auth::user()->isAdminFinance() || Auth::user()->isOwner())
                        <li>
                            <a href="{{ route('approvals.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('approvals.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="check-square" class="w-4 h-4 {{ request()->routeIs('approvals.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                    <span>Approval Disposisi</span>
                                </span>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('payments.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('payments.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i data-lucide="receipt" class="w-4 h-4 {{ request()->routeIs('payments.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                <span>Riwayat Pembayaran</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg transition {{ request()->routeIs('reports.*') ? 'text-white bg-slate-800 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i data-lucide="bar-chart-3" class="w-4 h-4 {{ request()->routeIs('reports.*') ? 'text-primary-400' : 'text-slate-400' }}"></i>
                                <span>Laporan & Analitik</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        @endauth
    </div>

    <!-- User Profile Footer -->
    <div class="p-3 border-t border-slate-800 bg-slate-950/40">
        @auth
            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/60 border border-slate-700/50">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-primary-700 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-medium text-white truncate">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-slate-400 truncate">{{ Auth::user()->role?->display_name }}</div>
                    </div>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold transition">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Masuk Sistem</span>
            </a>
        @endauth
    </div>
</aside>
