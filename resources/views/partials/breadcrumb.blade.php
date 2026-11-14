@if (isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs) > 0)
    <nav class="flex items-center text-sm text-slate-500 mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            <li class="inline-flex items-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-primary-700 font-medium transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    <span>Beranda</span>
                </a>
            </li>
            @foreach ($breadcrumbs as $crumb)
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                        @if (!empty($crumb['url']) && !$loop->last)
                            <a href="{{ $crumb['url'] }}" class="ml-1 text-slate-600 hover:text-primary-700 font-medium md:ml-2 transition-colors">
                                {{ $crumb['label'] }}
                            </a>
                        @else
                            <span class="ml-1 text-slate-900 font-semibold md:ml-2" aria-current="page">
                                {{ $crumb['label'] }}
                            </span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>
@endif

