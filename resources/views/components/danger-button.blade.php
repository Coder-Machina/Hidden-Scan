<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white font-semibold text-sm rounded-xl transition duration-200 cursor-pointer disabled:opacity-50']) }}>
    {{ $slot }}
</button>
