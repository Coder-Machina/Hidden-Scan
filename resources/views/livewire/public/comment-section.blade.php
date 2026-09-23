<div class="animate-fade-in">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-display font-bold text-xl text-chalk flex items-center gap-2">
            <svg class="w-6 h-6 text-violet" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
            Commentaires ({{ $comments->total() }})
        </h3>
    </div>

    {{-- Formulaire d'ajout --}}
    <div class="mb-8 p-4 bg-ink rounded-xl border border-line/30 focus-within:border-violet focus-within:shadow-[0_0_0_2px_rgba(155,123,255,0.1)] transition-all">
        <form wire:submit="addComment" class="space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-panel border border-line flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="flex-1 space-y-2">
                    <input type="text" wire:model="pseudo" placeholder="Pseudonyme (facultatif)" class="input-field !bg-panel !text-sm">
                    @error('pseudo') <span class="text-xs text-rose">{{ $message }}</span> @enderror

                    <textarea wire:model="content" rows="3" placeholder="Partagez votre avis..." class="input-field !bg-panel !text-sm resize-y" required></textarea>
                    @error('content') <span class="text-xs text-rose">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-line/30">
                <button type="submit" class="btn-primary !py-2 !px-6 !text-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="addComment">Publier</span>
                    <span wire:loading wire:target="addComment" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Envoi...
                    </span>
                </button>
            </div>
            
            @if (session()->has('message'))
                <div class="mt-2 text-xs text-mint flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('message') }}
                </div>
            @endif
        </form>
    </div>

    {{-- Liste des commentaires --}}
    <div class="space-y-4">
        @forelse($comments as $comment)
            <div class="p-4 bg-panel-hi/30 rounded-xl border border-line/30 animate-fade-in-up" style="animation-delay: {{ min($loop->index * 0.05, 0.5) }}s">
                <div class="flex items-start gap-3">
                    {{-- Avatar généré avec les initiales --}}
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet to-violet-deep flex items-center justify-center flex-shrink-0 text-white font-bold text-sm shadow-md">
                        {{ strtoupper(substr($comment->pseudo, 0, 1)) }}
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <h4 class="font-bold text-chalk">{{ $comment->pseudo }}</h4>
                            <span class="text-xs text-mist/60">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        
                        <p class="text-sm text-mist leading-relaxed mb-3 whitespace-pre-wrap">{{ $comment->content }}</p>
                        
                        <div class="flex items-center gap-4 text-xs font-semibold text-mist/70">
                            {{-- Likes feature --}}
                            <button wire:click="toggleLike({{ $comment->id }})" class="flex items-center gap-1.5 hover:text-rose transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                {{ $comment->likes_count ?? 0 }}
                            </button>
                            
                            {{-- Formulaire de réponse (bientôt) --}}
                            <button wire:click="replyTo({{ $comment->id }}, '{{ addslashes($comment->pseudo) }}')" class="flex items-center gap-1.5 hover:text-chalk transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                Répondre
                            </button>
                            
                            {{-- Bouton de signalement --}}
                            <button wire:click="report({{ $comment->id }})" onclick="confirm('Signaler ce commentaire à la modération ?') || event.stopImmediatePropagation()" class="flex items-center gap-1.5 hover:text-amber ml-auto transition" title="Signaler">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-panel-hi/20 rounded-xl border border-line/20">
                <p class="text-mist text-sm">Soyez le premier à donner votre avis !</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-6">
        {{ $comments->links() }}
    </div>
</div>