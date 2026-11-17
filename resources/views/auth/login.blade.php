<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log in · StudentVault</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-6 sm:px-8 lg:px-12">
        <header class="flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg text-lg font-extrabold tracking-tight focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-4" aria-label="StudentVault home">
                StudentVault.
            </a>
            <span class="hidden items-center gap-2 text-sm font-medium text-slate-500 sm:inline-flex"><i class="fa-solid fa-lock text-primary-700" aria-hidden="true"></i> Your private academic workspace</span>
        </header>

        <main class="flex flex-1 items-center py-10 sm:py-16">
            <div class="grid w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60 lg:grid-cols-2">
                <section class="relative hidden min-h-[620px] flex-col justify-between overflow-hidden bg-slate-100 p-12 text-slate-900 lg:flex" aria-labelledby="welcome-heading">
                    <div class="absolute -right-28 -top-28 h-96 w-96 rounded-full border border-slate-200" aria-hidden="true"></div>
                    <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full border border-slate-200" aria-hidden="true"></div>
                    <div class="absolute -bottom-36 -left-20 h-96 w-96 rounded-full bg-slate-200/70 blur-3xl" aria-hidden="true"></div>
                    <div class="relative z-10 inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white/70 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-600">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Built for privacy
                    </div>
                    <div class="relative z-10 max-w-md">
                        <h2 id="welcome-heading" class="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">Your work deserves a safer space.</h2>
                        <p class="mt-6 text-base leading-7 text-slate-600">Keep your notes, documents, and courses together in one private place built for student life.</p>
                        <div class="mt-10 flex flex-wrap gap-3 text-sm text-slate-600">
                            <span class="rounded-full border border-slate-300 bg-white/50 px-3 py-1.5"><i class="fa-solid fa-lock mr-2" aria-hidden="true"></i>Encrypted storage</span>
                            <span class="rounded-full border border-slate-300 bg-white/50 px-3 py-1.5"><i class="fa-solid fa-user-shield mr-2" aria-hidden="true"></i>Only yours</span>
                        </div>
                    </div>
                    <p class="relative z-10 text-xs text-slate-500">StudentVault · Private by default</p>
                </section>

                <section class="flex items-center px-6 py-10 sm:px-10 sm:py-14 lg:px-14" aria-labelledby="login-heading">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mb-8">
                            <h1 id="login-heading" class="text-3xl font-extrabold tracking-tight text-slate-900">Log in to your vault</h1>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Pick up where you left off. Your workspace is ready.</p>
                        </div>

                        <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                                <div class="relative">
                                    <i class="fa-solid fa-envelope pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="you@university.edu" class="block w-full rounded-xl border-slate-300 py-3 pl-11 pr-4 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-700 focus:ring-primary-700" />
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="rounded text-sm font-semibold text-primary-700 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2">Forgot password?</a>
                                    @endif
                                </div>
                                <div class="relative">
                                    <i class="fa-solid fa-lock pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                    <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password" class="block w-full rounded-xl border-slate-300 py-3 pl-11 pr-4 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-700 focus:ring-primary-700" />
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                                <input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-700" />
                                Remember me
                            </label>
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-700 px-5 py-3 text-sm font-bold text-white shadow-sm shadow-primary-700/20 transition hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2">
                                Log in <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                            </button>
                        </form>

                        @if (Route::has('register'))
                            <p class="mt-8 border-t border-slate-100 pt-6 text-center text-sm text-slate-500">New to StudentVault? <a href="{{ route('register') }}" class="font-bold text-primary-700 hover:text-primary-800">Create an account</a></p>
                        @endif
                    </div>
                </section>
            </div>
        </main>
        <footer class="text-center text-xs text-slate-400">Your notes and files stay yours.</footer>
    </div>
</body>
</html>
