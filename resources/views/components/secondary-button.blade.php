<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#1c1f2a] hover:bg-[#252837] text-gray-200 hover:text-white font-semibold text-sm rounded-xl transition duration-200 cursor-pointer disabled:opacity-50']) }}>
    {{ $slot }}
</button>
