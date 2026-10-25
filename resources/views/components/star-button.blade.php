@props(['action', 'starred' => false, 'label' => 'resource'])

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'inline-flex']) }}>
    @csrf
    @method('PATCH')

    <button
        type="submit"
        @class([
            'inline-flex h-9 w-9 items-center justify-center rounded-lg text-lg leading-none transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2',
            'text-amber-500' => $starred,
            'text-slate-400' => ! $starred,
        ])
        aria-label="{{ $starred ? 'Remove star from '.$label : 'Star '.$label }}"
        title="{{ $starred ? 'Remove star' : 'Add star' }}"
    >
        <span aria-hidden="true">{{ $starred ? '★' : '☆' }}</span>
    </button>
</form>
