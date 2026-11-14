<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk &mdash; JMM Disposisi | PT Jaya Mukti Mandiri</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @php
    $isProduction = app()->environment('production');
    $manifestPath = $isProduction ? '../public_html/build/manifest.json' : public_path('build/manifest.json');
    @endphp

    @if ($isProduction && file_exists($manifestPath))
    @php
    $manifest = json_decode(file_get_contents($manifestPath), true);
    @endphp
    <link rel="stylesheet" href="{{ config('app.url') }}/build/{{ $manifest['resources/css/app.css']['file'] }}">
    <script type="module" src="{{ config('app.url') }}/build/{{ $manifest['resources/js/app.js']['file'] }}"></script>
    @else
    @viteReactRefresh
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @endif
</head>

<body class="h-full antialiased font-sans bg-white text-slate-800">
    <div class="min-h-screen w-full flex flex-col lg:flex-row">
        <!-- LEFT PANEL: Enterprise Branding, Highlights & Identity (Full Height Left Side) -->
        <div class="relative hidden lg:flex lg:w-1/2 xl:w-[54%] bg-gradient-to-br from-slate-950 via-slate-900 to-[#02284c] text-white flex-col justify-between p-12 xl:p-16 overflow-hidden">
            <!-- Background Decorative Glows and Elements -->
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary-600/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-primary-400/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:28px_28px] opacity-10 pointer-events-none"></div>

            <!-- Top Brand Header -->
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 bg-white rounded-2xl p-2 shadow-xl flex items-center justify-center border border-white/20">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo PT Jaya Mukti Mandiri" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="text-lg font-bold tracking-tight text-white uppercase">JMM Disposisi</div>
                        <div class="text-xs text-slate-300 font-medium">PT Jaya Mukti Mandiri</div>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs text-emerald-300 font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Portal Internal Terproteksi</span>
                </div>
            </div>

            <!-- Middle Feature Showcase Content -->
            <div class="relative z-10 my-auto py-10 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-primary-500/15 border border-primary-400/30 text-primary-300 text-xs font-semibold uppercase tracking-wider mb-5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Enterprise Management Platform
                </div>

                <h1 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight tracking-tight">
                    Sistem Tata Kelola Pengajuan Disposisi & Realisasi Dana Terpadu.
                </h1>

                <p class="mt-4 text-sm xl:text-base text-slate-300 leading-relaxed">
                    Dirancang khusus untuk mendukung akuntabilitas, transparansi anggaran operasional, dan efisiensi birokrasi persetujuan multi-level di lingkungan PT Jaya Mukti Mandiri.
                </p>

                <!-- Value Props Cards -->
                <div class="mt-8 space-y-3.5">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-xs hover:bg-white/[0.07] transition">
                        <div class="w-10 h-10 rounded-xl bg-primary-500/20 text-primary-300 flex items-center justify-center flex-shrink-0 border border-primary-400/20">
                            <i data-lucide="git-pull-request" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-white">Hierarki Persetujuan Bertingkat</h2>
                            <p class="text-xs text-slate-300 mt-0.5">Alur disposisi otomatis dan transparan dari pemohon lapangan, manajer, jajaran direksi, hingga verifikasi keuangan.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-xs hover:bg-white/[0.07] transition">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center flex-shrink-0 border border-emerald-400/20">
                            <i data-lucide="banknote" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-white">Pencairan Dana & Rekening Terverifikasi</h2>
                            <p class="text-xs text-slate-300 mt-0.5">Validasi nomor rekening tujuan (karyawan, sopir, vendor) serta rekapitulasi pembayaran penuh maupun bertahap secara real-time.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-xs hover:bg-white/[0.07] transition">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center flex-shrink-0 border border-amber-400/20">
                            <i data-lucide="file-check-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-white">Audit Trail & Integritas Dokumen</h2>
                            <p class="text-xs text-slate-300 mt-0.5">Pencatatan log aktivitas yang tidak dapat diubah (immutable), cetak format resmi disposisi, dan ekspor analitik laporan keuangan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Left Footer -->
            <div class="relative z-10 pt-6 border-t border-white/10 flex items-center justify-between text-xs text-slate-400">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-primary-400"></i>
                        Enkripsi TLS 1.3
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="shield" class="w-3.5 h-3.5 text-emerald-400"></i>
                        Role-Based Access Control
                    </span>
                </div>
                <span>&copy; {{ date('Y') }} PT Jaya Mukti Mandiri</span>
            </div>
        </div>

        <!-- RIGHT PANEL: Authentication Form (Full Height, Spacious, Clean) -->
        <div
            x-data="{ showPassword: false, submitting: false }"
            class="w-full lg:w-1/2 xl:w-[46%] bg-white flex flex-col justify-between p-6 sm:p-10 lg:p-14 xl:p-16 min-h-screen overflow-y-auto">
            <!-- Mobile Brand Header (Visible only on small/medium screens) -->
            <div class="lg:hidden flex items-center justify-between pb-6 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-white rounded-xl p-2 shadow-sm border border-slate-200 flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo PT Jaya Mukti Mandiri" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="text-base font-bold text-slate-900 leading-tight uppercase">JMM Disposisi</div>
                        <div class="text-xs text-slate-500 font-medium">PT Jaya Mukti Mandiri</div>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sistem Aktif
                </span>
            </div>

            <!-- Form Container (Centered Vertically) -->
            <div class="w-full max-w-md mx-auto my-auto py-4">
                <div class="mb-8 text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold mb-3">
                        <i data-lucide="log-in" class="w-3.5 h-3.5 text-primary-600"></i>
                        Portal Masuk Staf & Direksi
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Selamat Datang Kembali
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Silakan masukkan alamat email dan kata sandi Anda untuk mengakses portal sistem disposisi.
                    </p>
                </div>

                <!-- Session Flash Messages -->
                @include('partials.alerts')

                <form method="POST" action="{{ route('login') }}" @submit="submitting = true" class="space-y-5">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@jmmdisposisi.my.id"
                                class="w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border @error('email') border-rose-500 bg-rose-50/20 text-rose-900 @else border-slate-200 bg-white @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition shadow-xs placeholder:text-slate-400">
                        </div>
                        @error('email')
                        <p class="mt-2 text-xs text-rose-600 font-medium flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span>{{ $message }}</span>
                        </p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Kata Sandi (Password) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi..."
                                class="w-full pl-10 pr-11 py-2.5 text-sm rounded-xl border @error('password') border-rose-500 bg-rose-50/20 text-rose-900 @else border-slate-200 bg-white @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-600 transition shadow-xs placeholder:text-slate-400">
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 focus:outline-none transition cursor-pointer"
                                tabindex="-1"
                                :title="showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'">
                                <i x-show="!showPassword" data-lucide="eye" class="w-4 h-4"></i>
                                <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-4 h-4"></i>
                            </button>
                        </div>
                        @error('password')
                        <p class="mt-2 text-xs text-rose-600 font-medium flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span>{{ $message }}</span>
                        </p>
                        @enderror
                    </div>

                    <!-- Remember Me & Timezone -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2.5 text-slate-600 hover:text-slate-900 cursor-pointer select-none group transition">
                            <input type="checkbox" name="remember">
                            <span class="font-medium group-hover:text-slate-900 transition">Ingat sesi saya</span>
                        </label>
                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            WIB (Asia/Jakarta)
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full py-3 px-4 rounded-xl bg-primary-600 hover:bg-primary-700 disabled:opacity-75 disabled:cursor-wait text-white font-bold text-sm shadow-md shadow-primary-600/30 hover:shadow-lg hover:shadow-primary-600/40 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all flex items-center justify-center gap-2 group cursor-pointer">
                            <span x-show="!submitting" class="flex items-center gap-2">
                                <span>Masuk ke Sistem</span>
                                <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5"></i>
                            </span>
                            <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                <span>Memverifikasi Akun...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bottom Support Footer -->
            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="help-circle" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Kendala akun? Hubungi Departemen IT Keuangan JMM.</span>
                </div>
                <div class="text-[11px] font-medium text-slate-400">
                    Versi 1.0 &bull; PT Jaya Mukti Mandiri
                </div>
            </div>
        </div>
    </div>
</body>

</html>