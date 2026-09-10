<div class="app-toast-host">
    @if ($show)
        <div
            wire:key="toast-{{ md5($type.$message) }}"
            x-data="{ show: false }"
            x-init="$nextTick(() => show = true); setTimeout(() => { show = false; setTimeout(() => $wire.dismiss(), 200) }, 4000)"
            x-show="show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="app-toast pointer-events-none sm:top-6 sm:right-6"
            role="status"
            aria-live="assertive"
        >
            <div @class([
                'pointer-events-auto flex w-[min(22rem,calc(100vw-2rem))] items-start gap-3 rounded-lg px-4 py-3 text-white shadow-lg',
                'bg-emerald-600' => $type !== 'error',
                'bg-red-600' => $type === 'error',
            ])>
                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center">
                    @if ($type === 'error')
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                        </svg>
                    @else
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </div>
                <p class="min-w-0 flex-1 text-sm font-medium leading-snug">{{ $message }}</p>
                <button type="button" class="shrink-0 text-white/80 hover:text-white" x-on:click="show = false; $wire.dismiss()" aria-label="Dismiss">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    @endif
</div>
