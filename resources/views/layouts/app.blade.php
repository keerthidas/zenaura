<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Zenaura' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600,700|outfit:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-paper text-ink">
    <div
        class="flex min-h-screen"
        x-data="{
            collapsed: {{ request()->routeIs('quotations.create', 'quotations.edit') ? 'true' : 'false' }},
            wasQuoteEditor: {{ request()->routeIs('quotations.create', 'quotations.edit') ? 'true' : 'false' }},
            pdfUrl: null,
            isQuoteEditor() {
                const path = window.location.pathname.replace(/\/+$/, '');
                return /\/quotations\/create$/.test(path) || /\/quotations\/[^/]+\/edit$/.test(path);
            },
            applyRoute() {
                const onEditor = this.isQuoteEditor();
                if (onEditor && ! this.wasQuoteEditor) {
                    this.collapsed = true;
                } else if (! onEditor) {
                    this.collapsed = localStorage.getItem('zenaura.sidebarCollapsed') === '1';
                }
                this.wasQuoteEditor = onEditor;
            },
            toggle() {
                this.collapsed = ! this.collapsed;
                localStorage.setItem('zenaura.sidebarCollapsed', this.collapsed ? '1' : '0');
            },
            openQuotationPdf(hide) {
                const base = this.pdfUrl;
                if (! base) return;
                const url = hide ? base + (base.includes('?') ? '&' : '?') + 'discount=hide' : base;
                this.pdfUrl = null;
                window.open(url, '_blank', 'noopener');
            },
        }"
        x-init="applyRoute()"
        x-on:livewire:navigated.window="applyRoute(); pdfUrl = null"
        x-on:open-quotation-pdf.window="pdfUrl = $event.detail.url"
        x-on:keydown.escape.window="pdfUrl = null"
    >
        <aside
            class="hidden w-64 shrink-0 flex-col bg-zenora-dark text-sand lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto {{ request()->routeIs('quotations.create', 'quotations.edit') ? '' : 'lg:flex' }}"
            :class="collapsed ? 'lg:!hidden' : 'lg:flex'"
        >
            <div class="flex items-start justify-between gap-2 px-6 pb-4 pt-7">
                <div>
                    <p class="font-display text-2xl font-semibold tracking-tight text-white">Zenaura</p>
                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-teal-200/80">Bathroom quotations</p>
                </div>
                <button type="button" class="mt-1 rounded-lg p-1.5 text-teal-100/80 hover:bg-white/10 hover:text-white" x-on:click="toggle" title="Collapse menu" aria-label="Collapse menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
            <nav class="flex flex-1 flex-col gap-1 px-3">
                @php
                    $links = [
                        ['dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['quotations.index', 'Quotations', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['delivery-notes.index', 'Delivery notes', 'M4 4h16v16H4z M8 9h8m-8 4h8m-8 4h5'],
                        ['leads.index', 'Sales CRM', 'M16 21v-2a4 4 0 00-8 0v2m12-9a4 4 0 01-3-1.35M4 12a4 4 0 003 3.87M12 11a4 4 0 100-8 4 4 0 000 8zm8 3a4 4 0 00-3-3.87'],
                        ['purchase-orders.index', 'LPOs', 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['suppliers.index', 'Suppliers', 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                        ['products.index', 'Products', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['clients.index', 'Clients', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['settings.company', 'Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                    ];
                @endphp
                @foreach ($links as [$route, $label, $icon])
                    <a href="{{ route($route) }}" wire:navigate
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ request()->routeIs(str($route)->before('.')->toString().'*') ? 'bg-white/10 text-white' : 'text-teal-100/80 hover:bg-white/5 hover:text-white' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="truncate text-sm text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-teal-200/70">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="text-xs text-teal-100/80 underline-offset-2 hover:text-white hover:underline">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-stone-200 bg-white/80 px-4 py-3 backdrop-blur lg:hidden">
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg hover:bg-sand" x-on:click="toggle" aria-label="Toggle menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <p class="font-display text-lg font-semibold">Zenora</p>
                </div>
                <a href="{{ route('quotations.create') }}" class="btn btn-primary py-1.5 text-xs">New quote</a>
            </header>
            <div class="hidden items-center border-b border-stone-200 bg-white px-3 py-2 lg:flex" x-show="collapsed" x-cloak>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-ink hover:bg-sand" x-on:click="toggle" aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    Menu
                </button>
            </div>
            <nav class="flex gap-2 overflow-x-auto border-b border-stone-200 bg-white px-3 py-2 text-sm lg:hidden" x-show="!collapsed" x-cloak>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('dashboard') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('dashboard') }}" wire:navigate>Home</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('quotations.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('quotations.index') }}" wire:navigate>Quotes</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('delivery-notes.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('delivery-notes.index') }}" wire:navigate>Delivery notes</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('leads.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('leads.index') }}" wire:navigate>CRM</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('purchase-orders.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('purchase-orders.index') }}" wire:navigate>LPOs</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('suppliers.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('suppliers.index') }}" wire:navigate>Suppliers</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('products.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('products.index') }}" wire:navigate>Products</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('clients.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('clients.index') }}" wire:navigate>Clients</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('settings.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('settings.company') }}" wire:navigate>Settings</a>
            </nav>
            <main class="flex-1 p-4 lg:p-8">
                {{ $slot }}
            </main>
        </div>

        <div
            x-cloak
            x-show="pdfUrl"
            x-transition.opacity
            x-on:click.self="pdfUrl = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quotation-pdf-choice-title"
        >
            <div class="card w-full max-w-md p-6">
                <h2 id="quotation-pdf-choice-title" class="font-display text-2xl">Open quotation PDF</h2>
                <p class="mt-2 text-sm text-stone-600">With discount keeps the discount column. Hide discount leaves it off and prints each sales price after the discount is already applied.</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-ghost" x-on:click="pdfUrl = null">Cancel</button>
                    <button type="button" class="btn btn-ghost" x-on:click="openQuotationPdf(true)">Hide discount</button>
                    <button type="button" class="btn btn-primary" x-on:click="openQuotationPdf(false)">With discount</button>
                </div>
            </div>
        </div>
    </div>
    <livewire:toast />
    @livewireScripts
</body>
</html>
