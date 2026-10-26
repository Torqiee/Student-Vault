<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><a href="{{ route('courses.index') }}" class="text-sm font-medium text-primary-700 hover:underline"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> All courses</a><h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $course->name }}</h1></div>
            <div class="flex gap-2"><a href="{{ route('courses.edit', $course) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a><x-confirm-action :name="'delete-course-'.$course->id" :action="route('courses.destroy', $course)" title="Delete this course?" message="Its notes and files will move to General." confirm-label="Delete Course" trigger-label="Delete" /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))<div class="rounded-lg bg-green-100 p-4 text-green-700" role="status">{{ session('success') }}</div>@endif
        @if ($course->description)<p class="whitespace-pre-wrap text-slate-600">{{ $course->description }}</p>@endif
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Notes</h2><a href="{{ route('notes.create', ['course_id' => $course->id]) }}" class="text-sm font-semibold text-primary-700 hover:underline"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> New Note</a></div>
                @forelse ($course->notes as $note)
                    <a href="{{ route('notes.show', $note) }}" class="block border-b border-slate-100 px-6 py-4 text-sm font-medium text-slate-800 last:border-0 hover:bg-slate-50">{{ $note->title }}</a>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">No notes in this course yet.</p>
                @endforelse
            </section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Files</h2><a href="{{ route('vault.create', ['course_id' => $course->id]) }}" class="text-sm font-semibold text-primary-700 hover:underline"><i class="fa-solid fa-arrow-up-from-bracket mr-1" aria-hidden="true"></i> Upload File</a></div>
                @forelse ($course->vaultFiles as $file)
                    <a href="{{ route('vault.download', $file) }}" class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4 text-sm last:border-0 hover:bg-slate-50"><span class="min-w-0 truncate font-medium text-slate-800">{{ $file->original_name }}</span><span class="shrink-0 capitalize text-slate-500">{{ $file->category }}</span></a>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-slate-500">No files in this course yet.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
