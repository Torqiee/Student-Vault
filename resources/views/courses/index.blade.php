<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div><h1 class="text-2xl font-bold text-slate-900">Courses</h1><p class="mt-1 text-sm text-slate-500">Organize notes and files by subject or project.</p></div>
            <a href="{{ route('courses.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ New Course</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>@endif
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">General</h2>
                <p class="mt-2 text-sm text-slate-500">Notes and files that are not assigned to a course.</p>
                <div class="mt-5 flex gap-4 text-sm font-semibold text-indigo-600">
                    <a href="{{ route('notes.index', ['course' => 'general']) }}" class="hover:underline">View notes</a>
                    <a href="{{ route('vault.index', ['course' => 'general']) }}" class="hover:underline">View files</a>
                </div>
            </div>
            @forelse ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <h2 class="truncate text-lg font-semibold text-slate-900">{{ $course->name }}</h2>
                    <p class="mt-2 line-clamp-2 min-h-10 text-sm text-slate-500">{{ $course->description ?: 'No description yet.' }}</p>
                    <p class="mt-5 text-sm text-slate-600">{{ $course->notes_count }} notes · {{ $course->vault_files_count }} files</p>
                </a>
            @empty
                <div class="flex flex-col justify-center rounded-2xl border border-slate-200 bg-white p-6 text-center">
                    <p class="font-semibold text-slate-800">No courses yet.</p>
                    <p class="mt-2 text-sm text-slate-500">Create a course to organize your notes and files.</p>
                    <a href="{{ route('courses.create') }}" class="mt-4 text-sm font-semibold text-indigo-600 hover:underline">Create Course</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
