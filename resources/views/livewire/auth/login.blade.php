<div class="relative flex min-h-screen overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#1a6b73_0%,#0e3d44_42%,#142426_100%)]"></div>
    <div class="absolute -right-24 top-16 h-80 w-80 rounded-full bg-copper/30 blur-3xl"></div>
    <div class="relative mx-auto flex w-full max-w-6xl flex-col justify-center gap-12 px-6 py-16 lg:flex-row lg:items-center">
        <div class="max-w-lg text-sand">
            <p class="text-xs uppercase tracking-[0.28em] text-teal-200/80">UAE · Bathroom accessories</p>
            <h1 class="mt-4 font-display text-5xl font-semibold text-white">Zenaura</h1>
            <p class="mt-4 text-lg leading-relaxed text-teal-50/80">
                Build clean, VAT-ready quotations for contractors and showrooms — product codes, discounts, and monthly quote numbers in one admin desk.
            </p>
        </div>
        <form wire:submit="login" class="card w-full max-w-md p-8">
            <h2 class="font-display text-2xl text-ink">Staff sign in</h2>
            <p class="mt-1 text-sm text-stone-500">Single operator access for quotation work.</p>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="label">Email</label>
                    <input type="email" wire:model="email" class="field" autocomplete="username">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Password</label>
                    <input type="password" wire:model="password" class="field" autocomplete="current-password">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-stone-600">
                    <input type="checkbox" wire:model="remember" class="rounded border-stone-300 text-zenora focus:ring-zenora">
                    Remember me
                </label>
            </div>
            <button class="btn btn-primary mt-6 w-full">Sign in</button>
        </form>
    </div>
</div>
