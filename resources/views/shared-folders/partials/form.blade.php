<form method="POST" action="{{ $sharedFolder ? route('shared-folders.update', $sharedFolder) : route('shared-folders.store') }}" class="space-y-5">
    @csrf
    @if ($sharedFolder)
        @method('PUT')
    @endif

    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700">Folder name</label>
        <input id="name" name="name" type="text" maxlength="100" required value="{{ old('name', $sharedFolder?->name) }}" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-primary-700 focus:ring-primary-700" placeholder="e.g. Information Security group">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-slate-700">Description <span class="font-normal text-slate-400">(optional)</span></label>
        <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-primary-700 focus:ring-primary-700">{{ old('description', $sharedFolder?->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
        <a href="{{ $sharedFolder ? route('shared-folders.show', $sharedFolder) : route('shared-folders.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">{{ $sharedFolder ? 'Save changes' : 'Create shared folder' }}</button>
    </div>
</form>
