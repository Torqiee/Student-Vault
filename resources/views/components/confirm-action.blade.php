@props([
    'name',
    'action',
    'title',
    'message',
    'confirmLabel',
    'triggerLabel' => null,
    'triggerClass' => 'rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700',
])

<button
    type="button"
    x-data=""
    x-on:click="$dispatch('open-modal', '{{ $name }}')"
    {{ $attributes->class([$triggerClass]) }}
>
    {{ $triggerLabel ?? $confirmLabel }}
</button>

<x-modal :name="$name" max-width="md" focusable>
    <div class="p-6">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600" aria-hidden="true">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h2 class="mt-5 text-lg font-semibold text-slate-900">{{ $title }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $message }}</p>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </button>

            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')

                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</x-modal>
