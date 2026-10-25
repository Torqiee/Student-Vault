<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Services\Vault\FileVaultService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class VaultFileController extends Controller
{
    public function index(Request $request)
    {
        $courseFilter = $request->query('course');
        $files = $request->user()->vaultFiles()->with('course');

        if ($courseFilter === 'general') {
            $files->whereNull('course_id');
        } elseif ($courseFilter !== null) {
            $course = $request->user()->courses()->findOrFail($courseFilter);
            $files->whereBelongsTo($course);
        }

        $files = $files->latest()->get();
        $courses = $request->user()->courses()->orderBy('name')->get();

        return view('vault.index', compact('files', 'courses', 'courseFilter'));
    }

    public function create(Request $request)
    {
        $courses = $request->user()->courses()->orderBy('name')->get();

        return view('vault.create', compact('courses'));
    }

    public function store(
        Request $request,
        FileVaultService $vault
    ) {
        $allowedExtensions = match ($request->input('category')) {
            'documents', 'assignments' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
            'identity' => 'pdf,png,jpg,jpeg,webp',
            'videos' => 'mp4,mov,webm,avi',
            default => '',
        };

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
            'category' => [
                'required',
                'in:documents,identity,assignments,videos',
            ],

            'file' => [
                'required',
                'file',
                'max:51200',
                'mimes:'.$allowedExtensions,
                'extensions:'.$allowedExtensions,
            ],
        ]);

        $vault->store(
            $request->user(),
            $request->file('file'),
            $validated['category'],
            $validated['course_id'] ?? null
        );

        return redirect()->route('vault.index')->with('success', 'File securely stored.');
    }

    public function download(
        Request $request,
        VaultFile $vaultFile,
        FileVaultService $vault
    ) {
        abort_unless(
            $vaultFile->user_id
                === $request->user()->id,
            403
        );

        try {
            $stream = $vault->decryptToStream($vaultFile);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['file' => 'Unable to open this file securely.']);
        }

        return response()->streamDownload(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $vaultFile->original_name, ['Content-Type' => $vaultFile->mime_type]);
    }

    public function destroy(
        Request $request,
        VaultFile $vaultFile,
        FileVaultService $vault
    ) {
        abort_unless(
            $vaultFile->user_id
                === $request->user()->id,
            403
        );

        $vault->delete($vaultFile);

        return redirect()
            ->route('vault.index')
            ->with(
                'success',
                'File deleted.'
            );
    }

    public function updateCourse(Request $request, VaultFile $vaultFile)
    {
        abort_unless($vaultFile->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
        ]);

        $vaultFile->update(['course_id' => $validated['course_id'] ?? null]);

        return back()->with('success', 'File course updated.');
    }
}
