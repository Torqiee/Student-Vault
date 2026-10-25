<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $courseFilter = $request->query('course');
        $notes = $request->user()->notes()->with('course');

        if ($courseFilter === 'general') {
            $notes->whereNull('course_id');
        } elseif ($courseFilter !== null) {
            $course = $request->user()->courses()->findOrFail($courseFilter);
            $notes->whereBelongsTo($course);
        }

        $notes = $notes->latest()->get();

        return view('notes.index', compact('notes', 'courseFilter'));
    }

    public function create(Request $request)
    {
        $courses = $request->user()->courses()->orderBy('name')->get();

        return view('notes.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
        ]);

        $request->user()->notes()->create($validated);

        return redirect()
            ->route('notes.index')
            ->with('success', 'Note created successfully.');
    }

    public function show(Request $request, Note $note)
    {
        abort_unless(
            $note->user_id === $request->user()->id,
            403
        );

        $note->load('course');

        return view('notes.show', compact('note'));
    }

    public function edit(Request $request, Note $note)
    {
        abort_unless(
            $note->user_id === $request->user()->id,
            403
        );

        $courses = $request->user()->courses()->orderBy('name')->get();

        return view('notes.edit', compact('note', 'courses'));
    }

    public function update(Request $request, Note $note)
    {
        abort_unless(
            $note->user_id === $request->user()->id,
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
        ]);

        $note->update($validated);

        return redirect()
            ->route('notes.index')
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless(
            $note->user_id === $request->user()->id,
            403
        );

        $note->delete();

        return redirect()
            ->route('notes.index')
            ->with('success', 'Note deleted successfully.');
    }
}
