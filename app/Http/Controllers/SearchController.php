<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $searchQuery = $request->input('q');
        $query = is_string($searchQuery)
            ? trim(mb_substr($searchQuery, 0, 100))
            : '';

        $notes = collect();
        $files = collect();
        $courses = collect();

        if ($query !== '') {
            $user = $request->user();

            // Notes are encrypted at rest; decrypt only this user's notes in memory.
            $notes = $user->notes()
                ->with('course')
                ->latest()
                ->get()
                ->filter(fn (Note $note): bool => $this->contains($note->title, $query)
                    || $this->contains($note->content, $query)
                    || $this->contains($note->course?->name, $query))
                ->take(50)
                ->values();

            $files = $user->vaultFiles()
                ->with('course')
                ->where(function (Builder $builder) use ($query): void {
                    $builder->where('original_name', 'like', "%{$query}%")
                        ->orWhere('category', 'like', "%{$query}%")
                        ->orWhere('mime_type', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(50)
                ->get();

            $courses = $user->courses()
                ->withCount(['notes', 'vaultFiles'])
                ->where(function (Builder $builder) use ($query): void {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(50)
                ->get();
        }

        return view('search.index', compact('query', 'notes', 'files', 'courses'));
    }

    private function contains(?string $value, string $needle): bool
    {
        return $value !== null && mb_stripos($value, $needle) !== false;
    }
}
