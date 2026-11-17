@php
    $isEditing = is_array($entry);
    $currentLabel = old('label', $entry['label'] ?? '');
    $currentCategory = old('category', $entry['category'] ?? 'student_profile');
    $currentFields = $entry['fields'] ?? [];
    $currentIdentityFile = old('identity_file_id', $entry['identity_file_id'] ?? '');
    $longFields = ['address', 'other_notes'];
    $dateFields = ['identity_expiry'];
@endphp

<form method="POST" action="{{ $isEditing ? route('personal-vault.update', $entry['id']) : route('personal-vault.store') }}" x-data="{ category: @js($currentCategory) }" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
            <p class="font-semibold">The entry was not saved.</p>
            <p class="mt-1">{{ $errors->first() }}</p>
        </div>
    @endif

    <div>
        <label for="label" class="block text-sm font-medium text-slate-700">Entry name</label>
        <input id="label" name="label" type="text" maxlength="100" required value="{{ $currentLabel }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary-700 focus:ring-primary-700" placeholder="e.g. My student profile">
        <p class="mt-1 text-xs text-slate-500">The entry name is encrypted in the database too.</p>
        @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category" class="block text-sm font-medium text-slate-700">Information category</label>
        <select id="category" name="category" x-model="category" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary-700 focus:ring-primary-700">
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @error('fields') <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p> @enderror

    @foreach ($fieldGroups as $group => $keys)
        <fieldset x-show="category === '{{ $group }}'" :disabled="category !== '{{ $group }}'" class="space-y-4 rounded-xl border border-slate-200 p-4 sm:p-5">
            <legend class="px-2 text-sm font-semibold text-slate-800">{{ $categories[$group] }}</legend>
            @foreach ($keys as $key)
                @php
                    $fieldValue = old('fields.'.$key, $currentFields[$key] ?? '');
                    $inputType = $key === 'personal_email' ? 'email' : (in_array($key, $dateFields, true) ? 'date' : 'text');
                @endphp
                <div>
                    <label for="field_{{ $key }}" class="block text-sm font-medium text-slate-700">{{ $fieldLabels[$key] }}</label>
                    @if (in_array($key, $longFields, true))
                        <textarea id="field_{{ $key }}" name="fields[{{ $key }}]" rows="{{ $key === 'address' ? 3 : 5 }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary-700 focus:ring-primary-700">{{ $fieldValue }}</textarea>
                    @else
                        <input id="field_{{ $key }}" name="fields[{{ $key }}]" type="{{ $inputType }}" value="{{ $fieldValue }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary-700 focus:ring-primary-700">
                    @endif
                    @error('fields.'.$key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach

            @if ($group === 'identity')
                <div class="border-t border-slate-200 pt-4">
                    <label for="identity_file_id" class="block text-sm font-medium text-slate-700">Link an identity scan from Files (optional)</label>
                    <select id="identity_file_id" name="identity_file_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary-700 focus:ring-primary-700">
                        <option value="">No linked file</option>
                        @foreach ($identityFiles as $file)
                            <option value="{{ $file->id }}" @selected((string) $currentIdentityFile === (string) $file->id)>{{ $file->original_name }}</option>
                        @endforeach
                    </select>
                    @error('identity_file_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($identityFiles->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">No identity documents found. Upload one first from Files using the Identity Document category.</p>
                    @else
                        <p class="mt-2 text-sm text-slate-500">Only identity files owned by your account can be linked. The file remains encrypted separately in private storage.</p>
                    @endif
                </div>
            @endif
        </fieldset>
    @endforeach

    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
        <a href="{{ route('personal-vault.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-primary-700 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-800">{{ $isEditing ? 'Save changes' : 'Upload Data' }}</button>
    </div>
</form>
