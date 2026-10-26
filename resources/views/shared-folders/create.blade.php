<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Create shared folder</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="mb-6 text-sm text-slate-600">Bring notes and files from different people into one private workspace. You can invite members after creating the folder.</p>
            @include('shared-folders.partials.form', ['sharedFolder' => null])
        </div>
    </div>
</x-app-layout>
