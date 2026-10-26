<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add to Personal Vault</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="mb-6 text-sm leading-6 text-slate-600">Data is encrypted by the backend using the project's AES-256-GCM implementation. Your submitted personal values are not flashed back into the session if validation fails.</p>
                @include('personal-vault.partials.form', ['entry' => null])
            </div>
        </div>
    </div>
</x-app-layout>
