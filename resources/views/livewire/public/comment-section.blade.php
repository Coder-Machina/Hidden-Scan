<div class="mt-8">
    <h3 class="text-lg font-bold mb-4 text-[#ece9f7]">Commentaires</h3>

    {{-- Formulaire --}}
    <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4 mb-6">
        @if($replyTo)
            <div class="flex items-center justify-between mb-3 bg-[#2a2445] rounded-lg px-3 py-2 text-sm">
                <span class="text-[#a39fc0]">Réponse à <strong class="text-[#9b7bff]">{{ $replyPseudo }}</strong></span>
                <button wire:click="cancelReply" class="text-[#a39fc0] hover:text-[#ece9f7]">✕</button>
            </div>
        @endif
        <div class="flex flex-col gap-3">
            <input
                    wire:model="pseudo"
                    type="text"
                    placeholder="Ton pseudo"
                    x-data
                    x-init="
                        const saved = localStorage.getItem('hiddenscan_pseudo');
                        if (saved) $wire.set('pseudo', saved);
                    "
                    x-on:change="localStorage.setItem('hiddenscan_pseudo', $event.target.value)"
                    class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] placeholder-[#a39fc0] focus:outline-none focus:border-[#9b7bff] w-48"
                >
            @error('pseudo') <span class="text-[#ff7ba8] text-xs">{{ $message }}</span> @enderror
            <textarea
                wire:model="content"
                placeholder="Ton commentaire..."
                rows="3"
                class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] placeholder-[#a39fc0] focus:outline-none focus:border-[#9b7bff] resize-none"
            ></textarea>
            @error('content') <span class="text-[#ff7ba8] text-xs">{{ $message }}</span> @enderror
            <button
                wire:click="submit"
                class="self-end bg-[#9b7bff] hover:bg-[#7c5cff] text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Envoyer
            </button>
        </div>
    </div>

    {{-- Liste des commentaires --}}
    @forelse($comments as $comment)
        <div class="mb-4">
            <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-[#9b7bff] text-sm">{{ $comment->pseudo }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-[#a39fc0]">{{ $comment->created_at->diffForHumans() }}</span>
                        <button
                            wire:click="replyTo({{ $comment->id }}, '{{ $comment->pseudo }}')"
                            class="text-xs text-[#a39fc0] hover:text-[#9b7bff] transition"
                        >
                            Répondre
                        </button>
                    </div>
                </div>
                <p class="text-sm text-[#ece9f7]">{{ $comment->content }}</p>
            </div>

            {{-- Réponses --}}
            @foreach($comment->replies as $reply)
                <div class="ml-8 mt-2 bg-[#1d1930] border border-[#3d3660] rounded-xl p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-[#9b7bff] text-sm">{{ $reply->pseudo }}</span>
                        <span class="text-xs text-[#a39fc0]">{{ $reply->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-[#ece9f7]">{{ $reply->content }}</p>
                </div>
            @endforeach
        </div>
    @empty
        <p class="text-[#a39fc0] text-sm text-center py-6">Aucun commentaire pour le moment. Sois le premier !</p>
    @endforelse
</div>