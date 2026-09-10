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
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 flex-col bg-zenora-dark text-sand lg:flex">
            <div class="px-6 pb-4 pt-7">
                <p class="font-display text-2xl font-semibold tracking-tight text-white">Zenora</p>
                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-teal-200/80">Bathroom quotations</p>
            </div>
            <nav class="flex flex-1 flex-col gap-1 px-3">
                @php
                    $links = [
                        ['dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['quotations.index', 'Quotations', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
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
                <p class="font-display text-lg font-semibold">Zenora</p>
                <a href="{{ route('quotations.create') }}" class="btn btn-primary py-1.5 text-xs">New quote</a>
            </header>
            <nav class="flex gap-2 overflow-x-auto border-b border-stone-200 bg-white px-3 py-2 text-sm lg:hidden">
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('dashboard') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('dashboard') }}" wire:navigate>Home</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('quotations.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('quotations.index') }}" wire:navigate>Quotes</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('products.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('products.index') }}" wire:navigate>Products</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('clients.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('clients.index') }}" wire:navigate>Clients</a>
                <a class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs('settings.*') ? 'bg-zenora-dark text-white' : 'bg-sand' }}" href="{{ route('settings.company') }}" wire:navigate>Settings</a>
            </nav>
            <main class="flex-1 p-4 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    <livewire:toast />
    @livewireScripts
</body>
</html>
