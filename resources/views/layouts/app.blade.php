<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'StudentVault') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        @include('layouts.navigation')
        <div class="min-w-0 flex-1 lg:pl-64">
            <div class="flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:hidden">
                <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Open navigation" aria-controls="app-sidebar" :aria-expanded="sidebarOpen.toString()">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <a href="{{ route('dashboard') }}" class="text-base font-bold tracking-tight text-indigo-700">StudentVault</a>
            </div>
            @isset($header)
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
                </header>
            @endisset
            <main id="main-content" class="min-w-0">{{ $slot }}</main>
        </div>
    </div>
</body>
</html>
