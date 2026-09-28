@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition-all duration-200 outline-none w-full shadow-inner']) }}>
