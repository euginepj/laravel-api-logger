@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div>
            <p class="text-slate-500 dark:text-slate-400">
                Showing
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $paginator->firstItem() ?? 0 }}</span>
                to
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $paginator->lastItem() ?? 0 }}</span>
                of
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($paginator->total()) }}</span>
                results
            </p>
        </div>

        <div class="inline-flex items-center gap-1 rounded-xl p-1 bg-slate-50 dark:bg-black/20 border border-zinc-200 dark:border-white/10">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-2.5 py-1.5 rounded-lg text-slate-400 dark:text-slate-600 cursor-not-allowed inline-flex items-center gap-1" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-2.5 py-1.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-white/10 hover:text-[var(--primary,#f31d25)] transition-all inline-flex items-center gap-1 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">Prev</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-2 py-1 text-slate-400 select-none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1.5 rounded-lg font-bold text-white bg-[var(--primary,#f31d25)] shadow-xs" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-white/10 hover:text-[var(--primary,#f31d25)] transition-all font-medium">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-2.5 py-1.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-white/10 hover:text-[var(--primary,#f31d25)] transition-all inline-flex items-center gap-1 font-medium">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="px-2.5 py-1.5 rounded-lg text-slate-400 dark:text-slate-600 cursor-not-allowed inline-flex items-center gap-1" aria-disabled="true">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
