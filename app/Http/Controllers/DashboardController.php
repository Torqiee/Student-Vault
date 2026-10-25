<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'notesCount' => $user->notes()->count(),
            'filesCount' => $user->vaultFiles()->count(),
            'coursesCount' => $user->courses()->count(),
            'storageBytes' => (int) $user->vaultFiles()->sum('original_size'),
            'recentNotes' => $user->notes()->latest()->limit(5)->get(),
            'recentFiles' => $user->vaultFiles()->latest()->limit(5)->get(),
        ]);
    }
}
