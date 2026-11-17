<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Private data access</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6">
        @if (session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</p>@endif
        @if ($errors->any())<p class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>@endif
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Request access to one item</h3>
            <p class="mt-1 text-sm text-slate-500">Ask the owner for their email and item ID. An approval does not open the item yet; secure key exchange is a later phase.</p>
            <form method="POST" action="{{ route('private-access.store') }}" class="mt-5 grid gap-4 sm:grid-cols-3">
                @csrf
                <label class="block text-sm font-medium text-slate-700">Owner email<input type="email" name="owner_email" required class="mt-1 block w-full rounded-lg border-slate-300" autocomplete="email"></label>
                <label class="block text-sm font-medium text-slate-700">Item type<select name="resource_type" required class="mt-1 block w-full rounded-lg border-slate-300"><option value="personal_data">Personal Vault entry</option><option value="vault_file">File, image or document</option></select></label>
                <label class="block text-sm font-medium text-slate-700">Item ID<input type="number" min="1" name="resource_id" required class="mt-1 block w-full rounded-lg border-slate-300"></label>
                <div class="sm:col-span-3"><button class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">Send request</button></div>
            </form>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Requests for your data</h3>
            <div class="mt-4 space-y-3">
                @forelse ($incoming as $item)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                        <p class="text-sm text-slate-700"><strong>{{ $item->requester->name }}</strong> ({{ $item->requester->email }}) requested {{ $item->resource_type === 'personal_data' ? 'Personal Vault entry' : 'File' }} #{{ $item->resource_id }} · <span class="font-semibold">{{ ucfirst($item->status) }}</span></p>
                        <div class="flex gap-2">
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('private-access.approve', $item) }}">@csrf<button class="rounded-lg bg-primary-700 px-3 py-2 text-sm text-white">Approve</button></form>
                                <form method="POST" action="{{ route('private-access.decline', $item) }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">Decline</button></form>
                            @elseif ($item->status === 'approved')
                                <form method="POST" action="{{ route('private-access.revoke', $item) }}">@csrf<button class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">Revoke</button></form>
                            @endif
                        </div>
                    </div>
                @empty <p class="text-sm text-slate-500">No incoming requests.</p> @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Your requests</h3>
            <div class="mt-4 space-y-3">
                @forelse ($outgoing as $item)
                    <p class="rounded-xl border border-slate-200 p-4 text-sm text-slate-700">{{ $item->resource_type === 'personal_data' ? 'Personal Vault entry' : 'File' }} #{{ $item->resource_id }} · {{ $item->owner->email }} · <strong>{{ ucfirst($item->status) }}</strong></p>
                @empty <p class="text-sm text-slate-500">No outgoing requests.</p> @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
