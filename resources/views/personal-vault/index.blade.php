<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Personal Vault</h2>
                <p class="mt-1 text-sm text-gray-500">Private student information, encrypted field-by-field.</p>
            </div>
            <a href="{{ route('personal-vault.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                + Add personal data
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <strong>Privacy reminder:</strong> Personal fields and labels are encrypted before database storage. Upload identity scans first from <a href="{{ route('vault.create') }}" class="font-semibold underline">Files → Upload File → Identity Document</a>, then link the file to an Identity Information entry.
            </div>

            @forelse ($entries as $entry)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="break-words text-lg font-semibold text-slate-900">{{ $entry['label'] }}</h3>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $categories[$entry['category']] ?? ucfirst(str_replace('_', ' ', $entry['category'])) }}</span>
                                @if ($entry['error'])
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Could not decrypt</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Updated {{ $entry['updated_at']?->format('d M Y, H:i') ?? '—' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('personal-vault.edit', $entry['id']) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</a>
                            <form method="POST" action="{{ route('personal-vault.destroy', $entry['id']) }}" onsubmit="return confirm('Delete this personal information entry? The linked identity file will remain in Files.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </div>

                    @if (! $entry['error'])
                        @if (count($entry['fields']) > 0)
                            <details class="mt-5 rounded-xl bg-slate-50 p-4">
                                <summary class="cursor-pointer select-none text-sm font-semibold text-slate-700">View encrypted personal fields</summary>
                                <dl class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                    @foreach ($entry['fields'] as $key => $value)
                                        @if (trim((string) $value) !== '')
                                            <div class="min-w-0">
                                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                                <dd class="mt-1 whitespace-pre-wrap break-words text-sm text-slate-900">{{ $value }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </details>
                        @endif

                        @if ($entry['identity_file'])
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100 p-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">Linked identity document</p>
                                    <p class="mt-1 break-all text-sm text-slate-500">{{ $entry['identity_file']->original_name }}</p>
                                </div>
                                <a href="{{ route('vault.download', $entry['identity_file']) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">Secure download</a>
                            </div>
                        @elseif ($entry['category'] === 'identity')
                            <p class="mt-4 text-sm text-slate-500">No identity scan linked. Edit this entry to connect a file from Files.</p>
                        @endif
                    @else
                        <p class="mt-4 text-sm text-red-700">The encrypted entry could not be verified/decrypted. Sensitive values are hidden; check the server log before editing it.</p>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-700" aria-hidden="true">♙</div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Your Personal Vault is empty</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Add student information, contact details, emergency contacts, or identity details. Sensitive fields are encrypted automatically.</p>
                    <a href="{{ route('personal-vault.create') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">Add your first entry</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
