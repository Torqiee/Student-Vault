<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Edit Course</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('courses.update', $course) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf @method('PUT')
            <div><x-input-label for="name" value="Course name" /><x-text-input id="name" name="name" type="text" :value="old('name', $course->name)" class="mt-1 block w-full" required /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
            <div><x-input-label for="description" value="Description (optional)" /><textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-700 focus:ring-primary-700">{{ old('description', $course->description) }}</textarea><x-input-error :messages="$errors->get('description')" class="mt-2" /></div>
            <div class="flex justify-end gap-3"><a href="{{ route('courses.show', $course) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancel</a><button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Save changes</button></div>
        </form>
    </div>
</x-app-layout>
