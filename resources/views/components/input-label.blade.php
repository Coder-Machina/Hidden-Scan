@props(['value'])

<label {{ $attributes->merge(['class' => 'text-label text-[var(--text-2)] block mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
