<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 &mdash; Akses Ditolak | JMM Disposisi</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 antialiased font-sans bg-slate-100">
    <div class="max-w-md w-full text-center bg-white rounded-2xl p-8 border border-slate-200 shadow-xl space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
            <i data-lucide="shield-alert" class="w-8 h-8"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">403 &mdash; Akses Ditolak</h1>
        <p class="text-xs text-slate-500 leading-relaxed">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau kewenangan untuk membuka halaman ini.' }}
        </p>
        <div class="pt-4 border-t border-slate-100">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>
</body>
</html>

