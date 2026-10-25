@props(['courses', 'selected' => null])

<div>
    <x-input-label for="course_id" value="Course" />
    <select id="course_id" name="course_id" {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>
        <option value="">General (no course)</option>
        @foreach ($courses as $course)
            <option value="{{ $course->id }}" @selected((string) $selected === (string) $course->id)>{{ $course->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
</div>
