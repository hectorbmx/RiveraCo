@props([
    'name',
    'label',
    'value' => '',
    'span' => 'md:col-span-2 md:max-w-44',
])

<div class="{{ $span }}">
    <label for="{{ $name }}" class="block text-xs font-semibold text-white/85 mb-1">{{ $label }}</label>
    <input id="{{ $name }}"
           type="date"
           name="{{ $name }}"
           value="{{ $value }}"
           {{ $attributes->merge(['class' => 'w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300/70']) }}>
</div>
