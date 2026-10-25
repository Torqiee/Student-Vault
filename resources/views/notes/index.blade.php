<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                {{ $courseFilter === 'general' ? 'General Notes' : 'My Notes' }}
            </h2>

            <a
                href="{{ route('notes.create') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white"
            >
                New Note
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">

                @forelse ($notes as $note)

                    <a
                        href="{{ route('notes.show', $note) }}"
                        class="block rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md"
                    >
                        <h3 class="font-semibold text-gray-900">
                            {{ $note->title }}
                        </h3>

                        <p class="mt-2 line-clamp-3 text-sm text-gray-500">
                            {{ $note->content }}
                        </p>

                        <p class="mt-3 text-xs font-medium text-indigo-600">{{ $note->course?->name ?? 'General' }}</p>

                        <p class="mt-4 text-xs text-gray-400">
                            {{ $note->updated_at->diffForHumans() }}
                        </p>
                    </a>

                @empty

                    <div class="col-span-full py-12 text-center text-gray-500">
                        You don't have any notes yet.
                    </div>

                @endforelse

            </div>

        </div>
    </div>
</x-app-layout>
