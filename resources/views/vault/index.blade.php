<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $courseFilter === 'general' ? 'General Files' : 'My File Vault' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">Your encrypted academic files, organized.</p>
            </div>

            <a
                href="{{ route('vault.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                Upload File
            </a>

        </div>

    </x-slot>


    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        @if(session('success'))

            <div class="rounded-lg bg-green-100 p-4 text-green-700" role="status">
                {{ session('success') }}
            </div>

        @endif

        @if($errors->has('file'))
            <div class="rounded-lg bg-red-100 p-4 text-red-700" role="alert">
                {{ $errors->first('file') }}
            </div>
        @endif

        <form method="GET" action="{{ route('vault.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            @if ($courseFilter !== null)
                <input type="hidden" name="course" value="{{ $courseFilter }}">
            @endif

            <div class="min-w-40">
                <label for="file-category" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                <select id="file-category" name="category" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All categories</option>
                    @foreach (['documents', 'identity', 'assignments', 'videos'] as $option)
                        <option value="{{ $option }}" @selected($category === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label for="file-sort" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort by</label>
                <select id="file-sort" name="sort" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="newest" @selected($sort === 'newest')>Newest first</option>
                    <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                    <option value="name" @selected($sort === 'name')>Name A–Z</option>
                    <option value="size_desc" @selected($sort === 'size_desc')>Largest first</option>
                    <option value="size_asc" @selected($sort === 'size_asc')>Smallest first</option>
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply</button>

            @if ($category !== null || $sort !== 'newest')
                <a href="{{ route('vault.index', $courseFilter !== null ? ['course' => $courseFilter] : []) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">

            <table class="w-full">

                <thead class="border-b border-slate-100 bg-slate-50">

                    <tr>

                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            File
                        </th>

                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Category
                        </th>

                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Course</th>

                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Size
                        </th>

                        <th class="p-4 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($files as $file)

                        <tr class="border-b border-slate-100 last:border-0">

                            <td class="p-4">

                                <div class="font-medium text-slate-900">
                                    {{ $file->original_name }}
                                </div>

                                <div class="text-sm text-slate-400">
                                    {{ $file->mime_type }}
                                </div>

                            </td>


                            <td class="p-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-600">{{ $file->category }}</span>
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


                            <td class="p-4 text-sm text-slate-600">

                                {{ number_format(
                                    $file->original_size / 1024,
                                    2
                                ) }} KB

                            </td>


                            <td class="p-4">

                                <div class="flex items-center justify-end gap-2">

                                    <x-star-button :action="route('vault.star', $file)" :starred="$file->starred_at !== null" :label="$file->original_name" />

                                    <a
                                        href="{{ route(
                                            'vault.download',
                                            $file
                                        ) }}"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
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
                                            class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700"
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
                                class="p-12 text-center text-slate-500"
                            >
                                @if ($category !== null || $courseFilter !== null)
                                    <p class="font-semibold text-slate-800">No files match these filters.</p>
                                    <p class="mt-2 text-sm">Try another category or course.</p>
                                    <a href="{{ route('vault.index') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Clear filters</a>
                                @else
                                    <p class="font-semibold text-slate-800">Your vault is empty.</p>
                                    <p class="mt-2 text-sm">Securely store your first document.</p>
                                    <a href="{{ route('vault.create') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Upload a file</a>
                                @endif
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>
