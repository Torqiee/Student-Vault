<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Private data access</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6">
        @if (session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</p>@endif
        @if ($errors->any())<p class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>@endif
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Request access to one item</h3>
            <p class="mt-1 text-sm text-slate-500">Ask the owner for their email and item ID. When the owner approves, StudentVault encrypts the item's access key with your public key and emails it to you. The owner can also send that encrypted key through another channel such as WhatsApp.</p>
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
                        @if ($item->status === 'approved' && $item->encrypted_symmetric_key)
                            <div class="w-full" x-data="{ copied: false }">
                                <label for="encrypted-key-{{ $item->id }}" class="block text-xs font-medium text-slate-600">Encrypted access key for {{ $item->requester->name }} (RSA-OAEP, safe to send by email or WhatsApp)</label>
                                <textarea id="encrypted-key-{{ $item->id }}" x-ref="key" readonly rows="3" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs text-slate-600">{{ $item->encrypted_symmetric_key }}</textarea>
                                <button type="button" class="mt-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700" x-on:click="navigator.clipboard.writeText($refs.key.value); copied = true" x-text="copied ? 'Copied' : 'Copy encrypted key'"></button>
                            </div>
                        @endif
                    </div>
                @empty <p class="text-sm text-slate-500">No incoming requests.</p> @endforelse
            </div>
        </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">
            Your requests
        </h3>

        <div class="mt-4 space-y-3">
            @forelse ($outgoing as $item)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-800">
                                {{ $item->resource_type === 'personal_data' ? 'Personal Vault entry' : 'File' }}
                                #{{ $item->resource_id }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Owner: {{ $item->owner->email }}
                            </p>

                            <p class="mt-1 text-sm font-semibold
                                @if ($item->status === 'approved') text-emerald-700
                                @elseif ($item->status === 'declined') text-red-700
                                @elseif ($item->status === 'revoked') text-red-700
                                @else text-amber-700
                                @endif">
                                Status: {{ ucfirst($item->status) }}
                            </p>
                        </div>

                        @if ($item->status === 'approved')
                            @if ($item->resource_type === 'personal_data')
                                <a
                                    href="{{ route('private-access.resources.personal-data', $item->id) }}"
                                    class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">
                                    Open shared data
                                </a>
                            @elseif ($item->resource_type === 'vault_file')
                                <a
                                    href="{{ route('private-access.resources.file', $item->id) }}"
                                    class="rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-800">
                                    Download shared file
                                </a>
                            @endif
                        @elseif ($item->status === 'pending')
                            <span class="text-xs text-slate-500">
                                Waiting for owner approval.
                            </span>
                        @elseif ($item->status === 'declined')
                            <span class="text-xs text-red-600">
                                Access was declined.
                            </span>
                        @elseif ($item->status === 'revoked')
                            <span class="text-xs text-red-600">
                                Access has been revoked.
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">
                    No outgoing requests.
                </p>
            @endforelse
        </div>
    </section>

    </div>
</x-app-layout>
