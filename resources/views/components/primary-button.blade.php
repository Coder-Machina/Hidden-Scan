<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#5865f2] hover:bg-[#4752c4] text-white font-bold text-sm rounded-xl transition duration-200 shadow-md shadow-[#5865f2]/20 active:scale-95 cursor-pointer disabled:opacity-50']) }}>
    {{ $slot }}
</button>
