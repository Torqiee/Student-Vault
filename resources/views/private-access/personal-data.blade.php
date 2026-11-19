
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Shared Personal Data
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Information shared by {{ $ownerName }}
                </p>
            </div>

            <a href="{{ route('private-access.index') }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to requests
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                You are viewing a specific item approved by its owner.
                This permission does not allow you to edit or delete the original data.
            </div>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ $entry['label'] }}
                    </h3>

                    <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">
                        {{ $categories[$entry['category']] ?? ucfirst(str_replace('_', ' ', $entry['category'])) }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-slate-500">
                    Shared by {{ $ownerName }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Updated {{ $entry['updated_at']?->format('d M Y, H:i') ?? '—' }}
                </p>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h4 class="text-sm font-semibold text-slate-800">
                        Personal information
                    </h4>

                    @if (count($entry['fields']) > 0)
                        <dl class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                            @foreach ($entry['fields'] as $key => $value)
                                @if (trim((string) $value) !== '')
                                    <div class="min-w-0">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                        </dt>

                                        <dd class="mt-1 whitespace-pre-wrap break-words text-sm text-slate-900">
                                            {{ $value }}
                                        </dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">
                            No additional information is available.
                        </p>
                    @endif
                </div>

                <div class="mt-6 border-t border-slate-100 pt-4">
                    <p class="text-xs text-slate-500">
                        This is a read-only view. Linked identity documents require separate access approval.
                    </p>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
