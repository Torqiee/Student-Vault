<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Edit Note
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            <div class="rounded-xl bg-white p-6 shadow-sm">

                <form
                    method="POST"
                    action="{{ route('notes.update', $note) }}"
                >

                    @csrf
                    @method('PUT')

                    <div>

                        <x-input-label
                            for="title"
                            value="Title"
                        />

                        <x-text-input
                            id="title"
                            name="title"
                            class="mt-1 block w-full"
                            value="{{ old('title', $note->title) }}"
                            required
                        />

                        <x-input-error
                            :messages="$errors->get('title')"
                            class="mt-2"
                        />

                    </div>


                    <div class="mt-6">

                        <x-input-label
                            for="content"
                            value="Content"
                        />

                        <textarea
                            id="content"
                            name="content"
                            rows="12"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >{{ old('content', $note->content) }}</textarea>

                        <x-input-error
                            :messages="$errors->get('content')"
                            class="mt-2"
                        />

                    </div>


                    <div class="mt-6">
                        <x-course-select :courses="$courses" :selected="old('course_id', $note->course_id)" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">

                        <a
                            href="{{ route('notes.show', $note) }}"
                            class="rounded-lg border px-4 py-2"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-primary-700 px-4 py-2 text-white"
                        >
                            Update Note
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

</x-app-layout>
