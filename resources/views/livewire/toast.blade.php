<div>
    @if ($show)
        <div
            wire:key="toast-{{ md5($title.$eyebrow) }}"
            x-data="{ show: true }"
            x-init="setTimeout(() => { show = false; $wire.dismiss() }, 7000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed inset-x-0 top-4 z-[120] flex justify-center px-4 sm:top-6"
            role="status"
            aria-live="assertive"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-zenora-dark text-white shadow-[0_28px_80px_-24px_rgba(14,61,68,0.95)] ring-4 ring-copper">
                <div class="flex items-start gap-4 p-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-copper shadow-inner">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-copper">{{ $eyebrow }}</p>
                        <p class="mt-1 break-all font-display text-3xl leading-none">{{ $title }}</p>
                        @if ($body)
                            <p class="mt-2 text-sm leading-snug text-teal-50">{{ $body }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="dismiss" class="rounded-full p-1 text-teal-100/80 hover:bg-white/10 hover:text-white" aria-label="Dismiss">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="h-1.5 bg-black/25">
                    <div class="toast-timer h-full bg-copper"></div>
                </div>
            </div>
        </div>
    @endif
</div>
