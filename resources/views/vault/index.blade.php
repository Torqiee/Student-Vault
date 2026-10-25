<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="text-xl font-semibold text-gray-800">
                {{ $courseFilter === 'general' ? 'General Files' : 'My File Vault' }}
            </h2>

            <a
                href="{{ route('vault.create') }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-white"
            >
                Upload File
            </a>

        </div>

    </x-slot>


    <div class="py-12">

        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if(session('success'))

                <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">
                    {{ session('success') }}
                </div>

            @endif

            @if($errors->has('file'))
                <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700" role="alert">
                    {{ $errors->first('file') }}
                </div>
            @endif


            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">

                <table class="w-full">

                    <thead class="border-b bg-gray-50">

                        <tr>

                            <th class="p-4 text-left">
                                File
                            </th>

                            <th class="p-4 text-left">
                                Category
                            </th>

                            <th class="p-4 text-left">Course</th>

                            <th class="p-4 text-left">
                                Size
                            </th>

                            <th class="p-4 text-right">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($files as $file)

                            <tr class="border-b">

                                <td class="p-4">

                                    <div class="font-medium">
                                        {{ $file->original_name }}
                                    </div>

                                    <div class="text-sm text-gray-400">
                                        {{ $file->mime_type }}
                                    </div>

                                </td>


                                <td class="p-4 capitalize">
                                    {{ $file->category }}
                                </td>

                                <td class="p-4">
                                    <form method="POST" action="{{ route('vault.course.update', $file) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="course_id" aria-label="Course for {{ $file->original_name }}" class="max-w-40 rounded-md border-gray-300 text-sm">
                                            <option value="">General</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}" @selected($file->course_id === $course->id)>{{ $course->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="text-sm font-semibold text-indigo-600 hover:underline">Save</button>
                                    </form>
                                </td>


                                <td class="p-4">

                                    {{ number_format(
                                        $file->original_size / 1024,
                                        2
                                    ) }} KB

                                </td>


                                <td class="p-4">

                                    <div class="flex justify-end gap-2">

                                        <a
                                            href="{{ route(
                                                'vault.download',
                                                $file
                                            ) }}"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white"
                                        >
                                            Download
                                        </a>


                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'vault.destroy',
                                                $file
                                            ) }}"
                                            onsubmit="return confirm('Delete this file?')"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="p-12 text-center text-gray-500"
                                >
                                    Your vault is empty.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>
