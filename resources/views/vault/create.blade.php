<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Upload to StudentVault
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            <div class="rounded-xl bg-white p-6 shadow-sm">

                <form
                    method="POST"
                    action="{{ route('vault.store') }}"
                    enctype="multipart/form-data"
                >

                    @csrf

                    <div>

                        <x-input-label
                            for="category"
                            value="Category"
                        />

                        <select
                            id="category"
                            name="category"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        >
                            <option value="">
                                Select category
                            </option>

                            <option value="assignments">
                                Assignment
                            </option>

                            <option value="documents">
                                Document
                            </option>

                            <option value="identity">
                                Identity Document
                            </option>

                            <option value="videos">
                                Video
                            </option>

                        </select>

                        <x-input-error
                            :messages="$errors->get('category')"
                            class="mt-2"
                        />

                    </div>


                    <div class="mt-6">

                        <x-input-label
                            for="file"
                            value="File"
                        />

                        <input
                            id="file"
                            type="file"
                            name="file"
                            class="mt-2 block w-full"
                            required
                        >

                        <x-input-error
                            :messages="$errors->get('file')"
                            class="mt-2"
                        />

                        <p class="mt-2 text-sm text-gray-500">
                            Maximum file size: 50 MB
                        </p>

                    </div>


                    <div class="mt-6">
                        <x-course-select :courses="$courses" :selected="old('course_id', request('course_id'))" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">

                        <a
                            href="{{ route('vault.index') }}"
                            class="rounded-lg border px-4 py-2"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-primary-700 px-4 py-2 text-white"
                        >
                            Secure Upload
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>
