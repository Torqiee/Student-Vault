<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Create Note
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            <div class="rounded-xl bg-white p-6 shadow-sm">

                <form
                    method="POST"
                    action="{{ route('notes.store') }}"
                >

                    @csrf

                    <div>
                        <x-input-label
                            for="title"
                            value="Title"
                        />

                        <x-text-input
                            id="title"
                            name="title"
                            class="mt-1 block w-full"
                            value="{{ old('title') }}"
                            required
                            autofocus
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
                        >{{ old('content') }}</textarea>

                        <x-input-error
                            :messages="$errors->get('content')"
                            class="mt-2"
                        />

                    </div>


                    <div class="mt-6">
                        <x-course-select :courses="$courses" :selected="old('course_id', request('course_id'))" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">

                        <a
                            href="{{ route('notes.index') }}"
                            class="rounded-lg border px-4 py-2"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-white"
                        >
                            Save Note
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>
