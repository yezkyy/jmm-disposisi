<header class="sticky top-0 z-20 flex h-20 min-h-[5rem] max-h-[5rem] flex-shrink-0 w-full items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur md:px-8 shadow-xs">
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button type="button" id="mobile-sidebar-toggle" class="inline-flex items-center justify-center rounded-xl p-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 md:hidden border border-slate-200" aria-label="Toggle Navigation">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="hidden sm:flex items-center gap-2.5 text-xs text-slate-600">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Sistem Aktif
            </span>
            <span class="text-slate-300">|</span>
            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200/60 font-medium">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </div>

    <div class="flex items-center gap-3 md:gap-4">
        <!-- User Profile Pill & Logout -->
        @auth
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-xs border border-primary-200 shadow-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden md:block text-left">
                        <div class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] text-slate-500 font-medium mt-0.5">{{ Auth::user()->role?->display_name ?? 'Pengguna' }}</div>
                    </div>
                </div>

                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/80 bg-slate-50 hover:bg-rose-50 text-slate-600 hover:text-rose-600 text-xs font-semibold transition" title="Keluar dari sistem">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary-600 text-white text-xs font-bold hover:bg-primary-700 transition shadow-sm">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Masuk</span>
            </a>
        @endauth
    </div>
</header>
