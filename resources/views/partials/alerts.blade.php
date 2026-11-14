@if (session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0"></i>
        <div class="flex-1 font-medium">
            {{ session('success') }}
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.showToast) {
                window.showToast('success', @json(session('success')));
            }
        });
    </script>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 p-4 text-rose-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0"></i>
        <div class="flex-1 font-medium">
            {{ session('error') }}
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.showToast) {
                window.showToast('error', @json(session('error')));
            }
        });
    </script>
@endif

@if (session('warning'))
    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0"></i>
        <div class="flex-1 font-medium">
            {{ session('warning') }}
        </div>
    </div>
@endif

@if (session('info'))
    <div class="mb-4 rounded-lg bg-sky-50 border border-sky-200 p-4 text-sky-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
        <i data-lucide="info" class="w-5 h-5 text-sky-600 mt-0.5 flex-shrink-0"></i>
        <div class="flex-1 font-medium">
            {{ session('info') }}
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 p-4 text-rose-800 text-sm flex items-start gap-3 shadow-sm" role="alert">
        <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0"></i>
        <div class="flex-1">
            <p class="font-semibold mb-1">Terjadi kesalahan validasi data:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

