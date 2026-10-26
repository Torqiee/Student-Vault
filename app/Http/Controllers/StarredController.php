<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StarredController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('starred.index', [
            'notes' => $user->notes()
                ->with('course')
                ->whereNotNull('starred_at')
                ->latest('starred_at')
                ->get(),
            'files' => $user->vaultFiles()
                ->with('course')
                ->whereNotNull('starred_at')
                ->latest('starred_at')
                ->get(),
        ]);
    }
}
