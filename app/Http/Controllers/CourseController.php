<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = $request->user()->courses()
            ->withCount(['notes', 'vaultFiles'])
            ->orderBy('name')
            ->get();

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('courses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $course = $request->user()->courses()->create($validated);

        return redirect()->route('courses.show', $course)->with('success', 'Course created.');
    }

    public function show(Request $request, Course $course): View
    {
        $this->authorizeOwner($request, $course);

        $course->load([
            'notes' => fn ($query) => $query->latest(),
            'vaultFiles' => fn ($query) => $query->latest(),
        ]);

        return view('courses.show', compact('course'));
    }

    public function edit(Request $request, Course $course): View
    {
        $this->authorizeOwner($request, $course);

        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $course->update($validated);

        return redirect()->route('courses.show', $course)->with('success', 'Course updated.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted. Its notes and files are now in General.');
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 403);
    }
}
