<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Personal Vault Entry</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="mb-6 text-sm leading-6 text-slate-600">Fields are decrypted only for this authenticated owner's edit form and re-encrypted before saving.</p>
                @include('personal-vault.partials.form', ['entry' => $entry])
            </div>
        </div>
    </div>
</x-app-layout>
