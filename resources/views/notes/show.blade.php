<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                {{ $note->title }}
            </h2>

            <a
                href="{{ route('notes.index') }}"
                class="text-sm text-gray-600 hover:text-gray-900"
            >
                <i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Back to Notes
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>
            @endif

            <div class="rounded-xl bg-white p-6 shadow-sm">

                <p class="mb-4 text-sm text-gray-500">
                    Course:
                    @if ($note->course)
                        <a href="{{ route('courses.show', $note->course) }}" class="font-medium text-primary-700 hover:underline">{{ $note->course->name }}</a>
                    @else
                        General
                    @endif
                </p>

                <div class="whitespace-pre-wrap text-gray-700">{{ $note->content }}</div>

                <div class="mt-8 border-t pt-4 text-sm text-gray-400">
                    Last updated {{ $note->updated_at->diffForHumans() }}
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a
                        href="{{ route('notes.edit', $note) }}"
                        class="rounded-lg bg-primary-700 px-4 py-2 text-white"
                    >
                        Edit
                    </a>

                    <x-confirm-action
                        :name="'trash-note-'.$note->id"
                        :action="route('notes.destroy', $note)"
                        title="Move this note to Trash?"
                        message="You can restore this note from Trash later."
                        confirm-label="Move to Trash"
                    />

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
