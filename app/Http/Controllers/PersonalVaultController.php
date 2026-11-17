<?php

namespace App\Http\Controllers;

use App\Models\PersonalVaultEntry;
use App\Services\Vault\PersonalVaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalVaultController extends Controller
{
    public function index(Request $request, PersonalVaultService $vault): View
    {
        $entries = $request->user()
            ->personalVaultEntries()
            ->with('identityFile')
            ->latest()
            ->get()
            ->map(fn (PersonalVaultEntry $entry): array => $vault->present($entry));

        $categories = PersonalVaultService::categories();
        $fieldLabels = PersonalVaultService::fieldLabels();

        return view('personal-vault.index', compact('entries', 'categories', 'fieldLabels'));
    }

    public function create(Request $request): View
    {
        return view('personal-vault.create', $this->formData($request));
    }

    public function store(Request $request, PersonalVaultService $vault): RedirectResponse
    {
        $validated = $this->validatedWithoutFlashing($request, 'personal-vault.create');
        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        $identityFileId = $validated['category'] === 'identity'
            ? (isset($validated['identity_file_id']) ? (int) $validated['identity_file_id'] : null)
            : null;

        if (! $vault->hasContent($validated['category'], $validated['fields'] ?? [], $identityFileId)) {
            return redirect()->route('personal-vault.create')->withErrors([
                'fields' => 'Isi minimal satu field untuk kategori yang dipilih atau tautkan dokumen identitas.',
            ]);
        }

        $vault->create($request->user(), $validated);

        return redirect()->route('personal-vault.index')->with('success', 'Personal information encrypted and saved.');
    }

    public function edit(Request $request, int $personalData, PersonalVaultService $vault): View|RedirectResponse
    {
        $record = $request->user()->personalVaultEntries()->with('identityFile')->findOrFail($personalData);
        $entry = $vault->present($record);

        if ($entry['error']) {
            return redirect()->route('personal-vault.index')->withErrors([
                'vault' => 'This entry could not be opened securely. It has not been changed.',
            ]);
        }

        return view('personal-vault.edit', array_merge($this->formData($request), compact('entry')));
    }

    public function update(Request $request, int $personalData, PersonalVaultService $vault): RedirectResponse
    {
        $record = $request->user()->personalVaultEntries()->findOrFail($personalData);
        $validated = $this->validatedWithoutFlashing($request, 'personal-vault.edit', ['personalData' => $record->id]);
        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        $identityFileId = $validated['category'] === 'identity'
            ? (isset($validated['identity_file_id']) ? (int) $validated['identity_file_id'] : null)
            : null;

        if (! $vault->hasContent($validated['category'], $validated['fields'] ?? [], $identityFileId)) {
            return redirect()->route('personal-vault.edit', $record->id)->withErrors([
                'fields' => 'Isi minimal satu field untuk kategori yang dipilih atau tautkan dokumen identitas.',
            ]);
        }

        $vault->update($record, $validated);

        return redirect()->route('personal-vault.index')->with('success', 'Personal information updated securely.');
    }

    public function destroy(Request $request, int $personalData): RedirectResponse
    {
        // Scope the lookup to the authenticated owner; never trust a URL ID by itself.
        $record = $request->user()->personalVaultEntries()->findOrFail($personalData);
        $record->delete();

        return redirect()->route('personal-vault.index')->with('success', 'Personal information deleted. Linked identity files remain in Files.');
    }

    private function formData(Request $request): array
    {
        return [
            'categories' => PersonalVaultService::categories(),
            'fieldLabels' => PersonalVaultService::fieldLabels(),
            'fieldGroups' => PersonalVaultService::fieldGroups(),
            'identityFiles' => $request->user()->vaultFiles()
                ->where('category', 'identity')
                ->orderBy('original_name')
                ->get(),
        ];
    }

    /** Validate without flashing submitted personal data into the session on failure. */
    private function validatedWithoutFlashing(
        Request $request,
        string $errorRoute,
        array $routeParameters = []
    ): array|RedirectResponse {
        $fieldKeys = implode(',', array_keys(PersonalVaultService::fieldLabels()));
        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in(array_keys(PersonalVaultService::categories()))],
            'identity_file_id' => [
                'nullable',
                'integer',
                Rule::exists('vault_files', 'id')->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->where('category', 'identity')),
            ],
            'fields' => ['nullable', 'array:'.$fieldKeys],
            'fields.student_id' => ['nullable', 'string', 'max:100'],
            'fields.university' => ['nullable', 'string', 'max:150'],
            'fields.faculty' => ['nullable', 'string', 'max:150'],
            'fields.study_program' => ['nullable', 'string', 'max:150'],
            'fields.academic_year' => ['nullable', 'string', 'max:30'],
            'fields.personal_phone' => ['nullable', 'string', 'max:50'],
            'fields.personal_email' => ['nullable', 'email:rfc', 'max:255'],
            'fields.address' => ['nullable', 'string', 'max:2000'],
            'fields.emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'fields.emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'fields.emergency_contact_relation' => ['nullable', 'string', 'max:80'],
            'fields.identity_number' => ['nullable', 'string', 'max:120'],
            'fields.identity_type' => ['nullable', 'string', 'max:80'],
            'fields.identity_expiry' => ['nullable', 'date_format:Y-m-d'],
            'fields.other_notes' => ['nullable', 'string', 'max:3000'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route($errorRoute, $routeParameters)->withErrors($validator);
        }

        return $validator->validated();
    }
}
