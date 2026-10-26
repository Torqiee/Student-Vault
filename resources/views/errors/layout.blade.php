<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('status') · StudentVault</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-6 sm:px-8 lg:px-12">
        <header>
            <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg text-lg font-extrabold tracking-tight focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-4" aria-label="StudentVault home">
                StudentVault.
            </a>
        </header>
        <main class="flex flex-1 items-center justify-center py-10 sm:py-16">
            <div class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center shadow-xl shadow-slate-200/60 sm:px-14 sm:py-16">
                <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-primary-50" aria-hidden="true"></div>
                <div class="absolute -bottom-24 -left-20 h-56 w-56 rounded-full border-[28px] border-primary-50" aria-hidden="true"></div>
                <div class="relative z-10">
                    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-primary-50 text-3xl text-primary-700" aria-hidden="true"><i class="@yield('icon')"></i></span>
                    <p class="mt-8 text-sm font-extrabold uppercase tracking-[0.25em] text-primary-700">Error @yield('status')</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">@yield('headline')</h1>
                    <p class="mx-auto mt-4 max-w-md text-base leading-7 text-slate-500">@yield('message')</p>
                    <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-700 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> {{ auth()->check() ? 'Go to dashboard' : 'Log in' }}</a>
                        <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2">Back to home</a>
                    </div>
                </div>
            </div>
        </main>
        <footer class="text-center text-xs text-slate-400">StudentVault · Your private academic workspace</footer>
    </div>
</body>
</html>
