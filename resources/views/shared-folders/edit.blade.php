<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Edit shared folder</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('shared-folders.partials.form', ['sharedFolder' => $sharedFolder])
        </div>
    </div>
</x-app-layout>
