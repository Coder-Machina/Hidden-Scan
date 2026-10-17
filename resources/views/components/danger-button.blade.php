<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary !bg-[var(--accent)] hover:!bg-[var(--accent-hover)]']) }}>
    {{ $slot }}
</button>
