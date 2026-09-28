<div class="w-full max-w-full overflow-hidden text-[#b8b8d8]" x-data="commentHelper()">
    <style>
        .comments-main-container {
            background-color: #131318;
            color: #b8b8d8;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow-x: hidden;
        }

        /* ─── Header ─── */
        .comments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .comments-header-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.2px;
        }

        .comments-counter-badge {
            background: #3a3a8a;
            color: #a0b0ff;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .comments-sort-group {
            display: flex;
            align-items: center;
            gap: 2px;
            background: #1a1a22;
            border: 1px solid #252535;
            padding: 3px;
            border-radius: 9999px;
        }

        .comments-sort-btn {
            font-size: 12px;
            font-weight: 500;
            color: #60608a;
            background: transparent;
            border: none;
            padding: 5px 12px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .comments-sort-btn:hover {
            color: #ffffff;
        }

        .comments-sort-btn.active {
            background: #ffffff;
            color: #111111;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        /* ─── Main Input Card ─── */
        .comments-input-card {
            background: #1a1a22;
            border: 1px solid #252535;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            box-sizing: border-box;
            width: 100%;
        }

        .comments-input-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }

        .comments-avatar-40 {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #202030;
            border: 1px solid #252535;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #d0d0f0;
            font-weight: 700;
            font-size: 14px;
            overflow: hidden;
            user-select: none;
        }

        .comments-avatar-40 img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comments-textarea {
            width: 100%;
            background: #202030;
            border: 1px solid #252535;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13.5px;
            color: #e8e8f0;
            resize: vertical;
            min-height: 76px;
            font-family: inherit;
            line-height: 1.5;
            outline: none;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .comments-textarea::placeholder {
            color: #383858;
        }

        .comments-textarea:focus {
            border-color: #5b6ef5;
        }

        .comments-pseudo-input {
            width: 100%;
            background: #202030;
            border: 1px solid #252535;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            color: #e8e8f0;
            margin-bottom: 10px;
            outline: none;
            box-sizing: border-box;
        }

        .comments-pseudo-input::placeholder {
            color: #383858;
        }

        .comments-pseudo-input:focus {
            border-color: #5b6ef5;
        }

        .comments-input-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 4px;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
        }

        .comments-format-tools {
            display: flex;
            align-items: center;
            gap: 2px;
            flex-wrap: wrap;
        }

        .comments-format-btn {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-radius: 6px;
            color: #60608a;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            padding: 0;
        }

        .comments-format-btn:hover {
            color: #ffffff;
            background: #202030;
        }

        .btn-publish-comment {
            background: linear-gradient(135deg, #5b6ef5, #7b8ef8);
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            padding: 7px 18px;
            border-radius: 9999px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(91, 110, 245, 0.35);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .btn-publish-comment:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(91, 110, 245, 0.45);
            filter: brightness(1.05);
        }

        .btn-publish-comment:active {
            transform: translateY(0);
        }

        /* ─── Comment Card ─── */
        .comments-list-wrap {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
        }

        .comment-card {
            background: #1a1a22;
            border: 1px solid #252535;
            border-radius: 10px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
            width: 100%;
        }

        .comment-card:hover {
            border-color: #2e2e42;
        }

        .comment-bg-illustration {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center right;
            opacity: 0.15;
            filter: saturate(1.3);
            pointer-events: none;
        }

        .comment-bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(26, 26, 34, 0.97) 0%, rgba(26, 26, 34, 0.75) 50%, rgba(26, 26, 34, 0.45) 100%);
            pointer-events: none;
        }

        .comment-content-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        /* ─── Comment Meta ─── */
        .comment-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            width: 100%;
        }

        .comment-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .comment-avatar-34 {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #202030;
            border: 1px solid #252535;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #d0d0f0;
            font-weight: 700;
            font-size: 13px;
            overflow: hidden;
            user-select: none;
        }

        .comment-avatar-34 img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comment-user-text {
            display: flex;
            align-items: baseline;
            gap: 6px;
            flex-wrap: wrap;
            min-width: 0;
        }

        .comment-pseudo {
            font-size: 13px;
            font-weight: 700;
            color: #d0d0f0;
            white-space: nowrap;
        }

        .comment-time {
            font-size: 11px;
            color: #60608a;
            white-space: nowrap;
        }

        /* ─── Comment Body ─── */
        .comment-body-text {
            color: #b8b8d8;
            font-size: 13.5px;
            line-height: 1.6;
            margin-left: 42px;
            margin-top: 2px;
            margin-bottom: 10px;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        /* ─── Comment Actions ─── */
        .comment-actions-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 42px;
            font-size: 12px;
            color: #60608a;
            flex-wrap: wrap;
            padding-top: 2px;
            width: calc(100% - 42px);
            box-sizing: border-box;
        }

        .comment-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: transparent;
            border: none;
            color: #60608a;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 4px;
            transition: color 0.15s ease;
            white-space: nowrap;
        }

        .comment-action-btn:hover {
            color: #ffffff;
        }

        .comment-action-btn.active-like {
            color: #7b8ef8;
            font-weight: 700;
        }

        .comment-action-btn.active-dislike {
            color: #93c5fd;
            font-weight: 700;
        }

        .comment-action-btn.btn-edit:hover {
            color: #a0b0ff;
        }

        .comment-action-btn.btn-delete:hover {
            color: #f87171;
        }

        .comment-report-btn {
            font-size: 11px;
            color: #404060;
            background: transparent;
            border: none;
            cursor: pointer;
            margin-left: auto;
            transition: color 0.15s ease;
            padding: 2px 4px;
            white-space: nowrap;
        }

        .comment-report-btn:hover {
            color: #8080a0;
        }

        /* ─── Inline Reply Form Box ─── */
        .inline-reply-box {
            margin-left: 42px;
            margin-top: 12px;
            background: #15151c;
            border: 1px solid #252535;
            border-radius: 10px;
            padding: 12px;
            box-sizing: border-box;
        }

        .inline-reply-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
            color: #707090;
        }

        .inline-reply-header strong {
            color: #7b8ef8;
        }

        .inline-reply-cancel-btn {
            background: transparent;
            border: none;
            color: #60608a;
            font-size: 11px;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .inline-reply-cancel-btn:hover {
            color: #f87171;
        }

        /* ─── Replies List ─── */
        .comment-replies-list {
            margin-left: 42px;
            margin-top: 12px;
            padding-left: 10px;
            border-left: 2px solid #252535;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-sizing: border-box;
        }

        .reply-item-card {
            background: #16161f;
            border: 1px solid #20202e;
            border-radius: 8px;
            padding: 10px 12px;
            position: relative;
            box-sizing: border-box;
            width: 100%;
        }

        .reply-body-text {
            margin-left: 36px;
        }

        .reply-actions-bar {
            margin-left: 36px;
            width: calc(100% - 36px);
        }

        /* ─── ADAPTATIONS MOBILES (< 640px) ─── */
        @media (max-width: 639px) {
            .comments-header {
                gap: 8px;
                margin-bottom: 14px;
            }
            .comments-header-title {
                font-size: 16px;
            }
            .comments-sort-group {
                width: 100%;
                display: flex;
                justify-content: space-between;
            }
            .comments-sort-btn {
                flex: 1;
                text-align: center;
                padding: 5px 6px;
                font-size: 11px;
            }
            .comments-input-card {
                padding: 10px 12px;
                margin-bottom: 16px;
                border-radius: 10px;
            }
            .comments-input-row {
                gap: 8px;
            }
            .comments-avatar-40 {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            .comments-textarea {
                padding: 8px 10px;
                font-size: 13px;
                min-height: 64px;
            }
            .comments-pseudo-input {
                padding: 6px 10px;
                font-size: 12px;
                margin-bottom: 8px;
            }
            .comments-input-footer {
                margin-top: 8px;
                gap: 6px;
            }
            .comments-format-tools {
                gap: 1px;
            }
            .comments-format-btn {
                width: 26px;
                height: 26px;
                font-size: 11px;
            }
            .btn-publish-comment {
                padding: 6px 14px;
                font-size: 12px;
            }
            .comment-card {
                padding: 10px 12px;
                border-radius: 8px;
            }
            .comment-avatar-34 {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }
            .comment-pseudo {
                font-size: 12px;
            }
            .comment-time {
                font-size: 10px;
            }
            .comment-body-text {
                margin-left: 0 !important;
                margin-top: 4px;
                margin-bottom: 8px;
                font-size: 13px;
            }
            .comment-actions-bar {
                margin-left: 0 !important;
                width: 100% !important;
                gap: 8px;
                font-size: 11px;
            }
            .comment-action-btn {
                font-size: 11px;
                gap: 3px;
            }
            .comment-report-btn {
                font-size: 10px;
            }
            .inline-reply-box {
                margin-left: 0 !important;
                margin-top: 10px;
                padding: 10px;
                border-radius: 8px;
            }
            .comment-replies-list {
                margin-left: 6px !important;
                padding-left: 8px !important;
                border-left-width: 1.5px;
                gap: 8px;
            }
            .reply-item-card {
                padding: 8px 10px;
            }
            .reply-body-text {
                margin-left: 0 !important;
            }
            .reply-actions-bar {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* ─── Bannière Connexion Requise ─── */
        .comments-auth-banner {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.12) 0%, rgba(20, 20, 32, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.35);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45);
        }

        .comments-auth-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.25);
        }

        .comments-auth-text-wrap {
            flex: 1;
            min-width: 240px;
        }

        .comments-auth-title {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 3px 0;
            letter-spacing: -0.2px;
        }

        .comments-auth-desc {
            font-size: 13px;
            color: #a0a0c0;
            margin: 0;
            line-height: 1.4;
        }

        .comments-auth-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .comments-btn-login {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #dc2626;
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
            transition: all 0.2s ease;
        }

        .comments-btn-login:hover {
            background: #ef4444;
            transform: translateY(-1px);
        }

        .comments-btn-register {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255, 255, 255, 0.06);
            color: #e8e8f4;
            border: 1px solid #2e2e42;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .comments-btn-register:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: #404058;
            transform: translateY(-1px);
        }

        @media (max-width: 640px) {
            .comments-auth-banner {
                padding: 14px 16px;
                gap: 12px;
            }
            .comments-auth-buttons {
                width: 100%;
            }
            .comments-btn-login,
            .comments-btn-register {
                flex: 1;
                justify-content: center;
                text-align: center;
            }
        }
    </style>

    <div class="comments-main-container">
        {{-- ═══ Header ═══ --}}
        <div class="comments-header">
            <div class="flex items-center gap-3">
                <h3 class="comments-header-title">Commentaires</h3>
                <span class="comments-counter-badge">
                    {{ $totalCount ?? ($comments ? $comments->total() : 0) }}
                </span>
            </div>

            @auth
            {{-- Tri à droite : 3 boutons (Récents / Anciens / Populaires) --}}
            <div class="comments-sort-group self-stretch sm:self-auto justify-end">
                <button type="button" wire:click="setSort('recent')" 
                        class="comments-sort-btn {{ $sortBy === 'recent' ? 'active' : '' }}">
                    Récents
                </button>
                <button type="button" wire:click="setSort('oldest')" 
                        class="comments-sort-btn {{ $sortBy === 'oldest' ? 'active' : '' }}">
                    Anciens
                </button>
                <button type="button" wire:click="setSort('popular')" 
                        class="comments-sort-btn {{ $sortBy === 'popular' ? 'active' : '' }}">
                    Populaires
                </button>
            </div>
            @endauth
        </div>

        @guest
            {{-- Invitation exclusive : connexion obligatoire pour voir, liker et poster des commentaires --}}
            <div class="comments-auth-banner">
                <div class="comments-auth-icon-wrap">
                    <svg class="w-6 h-6 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="comments-auth-text-wrap">
                    <h4 class="comments-auth-title">Rejoignez la discussion !</h4>
                    <p class="comments-auth-desc">Vous devez être inscrit et connecté avec votre compte pour voir les commentaires, réagir avec des likes et échanger avec la communauté.</p>
                </div>
                <div class="comments-auth-buttons">
                    <a href="{{ route('login') }}" class="comments-btn-login">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" class="comments-btn-register">
                        Créer un compte
                    </a>
                </div>
            </div>
        @endguest

        @auth
            {{-- ═══ Zone de saisie principale (Nouveau commentaire) ═══ --}}
            @if($isRestricted)
                <div class="mb-6 p-4 bg-red-950/30 rounded-xl flex items-center gap-3 text-red-400 text-sm" style="border: 1px solid #3b1820;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Votre compte ou adresse IP est restreint pour l'espace commentaires.</span>
                </div>
            @else
                <div class="comments-input-card">
                    <form wire:submit="submit">
                        <div class="comments-input-row">
                            {{-- Avatar circulaire 40px --}}
                            <div class="comments-avatar-40">
                                @if(Auth::user()->avatar)
                                    <img src="{{ Storage::url(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <span>{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                @endif
                            </div>

                            {{-- Textarea block --}}
                            <div class="flex-1 min-w-0">
                                <textarea wire:model="content" 
                                          x-ref="commentTextarea"
                                          rows="3" 
                                          placeholder="Écrire un commentaire en tant que {{ Auth::user()->name }}..." 
                                          class="comments-textarea"></textarea>
                                
                                @error('content') <span class="text-xs text-red-400 block mt-1">{{ $message }}</span> @enderror

                                {{-- Attached image preview --}}
                                <template x-if="attachedImage">
                                    <div class="relative inline-block mt-2">
                                        <img :src="attachedImage" class="max-h-24 rounded-lg" style="border: 1px solid #252535;" alt="Aperçu">
                                        <button type="button" @click="attachedImage = ''" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-700 transition">&times;</button>
                                    </div>
                                </template>

                                {{-- Footer: Formatting tools + Publier button --}}
                                <div class="comments-input-footer">
                                    <div class="comments-format-tools">
                                        <button type="button" @click="wrapText('**', '**')" class="comments-format-btn" title="Gras">B</button>
                                        <button type="button" @click="wrapText('*', '*')" class="comments-format-btn italic font-serif" title="Italique">I</button>
                                        <button type="button" @click="wrapText('~~', '~~')" class="comments-format-btn line-through" title="Barré">S</button>
                                        <button type="button" @click="insertQuote()" class="comments-format-btn font-serif" title="Citation">❝</button>
                                        <button type="button" @click="showImagePrompt()" class="comments-format-btn" title="Insérer une image">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>

                                    <button type="submit" class="btn-publish-comment" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="submit">Publier</span>
                                        <span wire:loading wire:target="submit" class="flex items-center gap-1.5">
                                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Envoi...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if (session()->has('message'))
                            <div class="mt-3 text-xs text-emerald-400 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ session('message') }}
                            </div>
                        @endif
                    </form>
                </div>
            @endif

            {{-- ═══ Liste des commentaires ═══ --}}
            <div class="comments-list-wrap">
            @forelse($comments as $comment)
                @php
                    $currentIpHash = hash('sha256', request()->ip() . config('app.key'));
                    $isOwner = auth()->check() ? ($comment->user_id === auth()->id()) : ($comment->ip_hash === $currentIpHash && !$comment->user_id);
                    $isAdmin = auth()->check() && auth()->user()->is_admin;
                    $canManage = $isOwner || $isAdmin;
                    
                    $isLiked = in_array($comment->id, $userLikedCommentIds ?? []);
                    $isDisliked = in_array($comment->id, $userDislikedCommentIds ?? []);

                    // Illustration manga en background
                    $bgImg = null;
                    if ($comment->commentable_type === 'App\Models\Manga' && $comment->commentable?->cover_image) {
                        $bgImg = Storage::url($comment->commentable->cover_image);
                    } elseif ($comment->commentable_type === 'App\Models\Chapter' && $comment->commentable?->manga?->cover_image) {
                        $bgImg = Storage::url($comment->commentable->manga->cover_image);
                    }
                    $hasIllustration = $bgImg && ($comment->id % 2 === 0 || $comment->likes_count > 1 || $comment->is_pinned);
                @endphp

                <div class="comment-card" x-data="{ openReplies: true }">
                    @if($hasIllustration && $bgImg)
                        <div class="comment-bg-illustration" style="background-image: url('{{ $bgImg }}');"></div>
                        <div class="comment-bg-overlay"></div>
                    @endif

                    <div class="comment-content-wrap">
                        {{-- Meta du commentaire --}}
                        <div class="comment-meta-row">
                            <div class="comment-user-info">
                                <div class="comment-avatar-34">
                                    @if($comment->user && $comment->user->avatar)
                                        <img src="{{ Storage::url($comment->user->avatar) }}" alt="{{ $comment->pseudo }}">
                                    @else
                                        <span>{{ strtoupper(substr($comment->pseudo ?: 'A', 0, 1)) }}</span>
                                    @endif
                                </div>

                                <div class="comment-user-text">
                                    <span class="comment-pseudo">{{ $comment->pseudo ?: 'Anonyme' }}</span>
                                    <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Corps du commentaire / Mode Édition direct --}}
                        @if($editingCommentId === $comment->id)
                            <div class="sm:ml-11 mb-3">
                                <textarea wire:model="editingContent" class="comments-textarea" rows="3"></textarea>
                                @error('editingContent') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                <div class="flex gap-2 mt-2">
                                    <button type="button" wire:click="updateComment" class="px-4 py-1.5 rounded-full bg-[#5b6ef5] text-white text-xs font-semibold cursor-pointer hover:bg-[#6c7ef8] transition">Enregistrer</button>
                                    <button type="button" wire:click="cancelEdit" class="px-4 py-1.5 rounded-full bg-[#202030] text-xs text-[#a0a0c0] hover:text-white cursor-pointer transition">Annuler</button>
                                </div>
                            </div>
                        @else
                            <div class="comment-body-text">{!! $comment->rendered_content !!}</div>
                        @endif

                        {{-- Barre d'actions : Like, Dislike, Répondre, Modifier, Supprimer, Signaler --}}
                        <div class="comment-actions-bar">
                            {{-- Like --}}
                            <button type="button" wire:click="toggleLike({{ $comment->id }})" 
                                    class="comment-action-btn {{ $isLiked ? 'active-like' : '' }}" 
                                    title="J'aime">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                </svg>
                                <span>{{ $comment->likes_count ?: '' }}</span>
                            </button>

                            {{-- Dislike --}}
                            <button type="button" wire:click="toggleDislike({{ $comment->id }})" 
                                    class="comment-action-btn {{ $isDisliked ? 'active-dislike' : '' }}" 
                                    title="Je n'aime pas">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="{{ $isDisliked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                                </svg>
                                <span>{{ $comment->dislikes_count ?: '' }}</span>
                            </button>

                            {{-- Répondre (ouvre le formulaire directement dessous) --}}
                            @auth
                                <button type="button" wire:click="setReplyTo({{ $comment->id }}, '{{ addslashes($comment->pseudo ?: 'Anonyme') }}')" class="comment-action-btn">
                                    Répondre
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="comment-action-btn" title="Connectez-vous pour répondre">
                                    Répondre
                                </a>
                            @endauth

                            {{-- Options Modifier & Supprimer si auteur ou admin --}}
                            @if($canManage)
                                <button type="button" wire:click="startEdit({{ $comment->id }})" class="comment-action-btn btn-edit">
                                    Modifier
                                </button>
                                <button type="button" wire:click="deleteComment({{ $comment->id }})" 
                                        onclick="confirm('Voulez-vous vraiment supprimer ce commentaire ?') || event.stopImmediatePropagation()" 
                                        class="comment-action-btn btn-delete text-red-400/80">
                                    Supprimer
                                </button>
                            @else
                                {{-- Signaler ultra discret --}}
                                <button type="button" wire:click="report({{ $comment->id }})" 
                                        onclick="confirm('Signaler ce commentaire à la modération ?') || event.stopImmediatePropagation()" 
                                        class="comment-report-btn">
                                    Signaler
                                </button>
                            @endif
                        </div>

                        {{-- ═══ Formulaire de réponse DIRECTEMENT sous ce commentaire ═══ --}}
                        @if($replyTo === $comment->id)
                            <div class="inline-reply-box animate-fade-in">
                                <div class="inline-reply-header">
                                    <span>Répondre à <strong>{{ $replyPseudo }}</strong></span>
                                    <button type="button" wire:click="cancelReply" class="inline-reply-cancel-btn">✕ Annuler</button>
                                </div>

                                <form wire:submit="submitReply">
                                    <textarea wire:model="replyContent" 
                                              x-ref="inlineReplyTextarea"
                                              rows="2" 
                                              placeholder="Écrivez votre réponse à {{ $replyPseudo }}..." 
                                              class="comments-textarea"
                                              style="min-height: 64px;"></textarea>

                                    @error('replyContent') <span class="text-xs text-red-400 block mt-1">{{ $message }}</span> @enderror

                                    <div class="comments-input-footer flex-wrap gap-2">
                                        <div class="comments-format-tools">
                                            <button type="button" @click="wrapInlineText('**', '**')" class="comments-format-btn" title="Gras">B</button>
                                            <button type="button" @click="wrapInlineText('*', '*')" class="comments-format-btn italic font-serif" title="Italique">I</button>
                                            <button type="button" @click="wrapInlineText('~~', '~~')" class="comments-format-btn line-through" title="Barré">S</button>
                                            <button type="button" @click="insertInlineQuote()" class="comments-format-btn font-serif" title="Citation">❝</button>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="cancelReply" class="px-3 py-1.5 text-xs text-[#60608a] hover:text-white transition">Annuler</button>
                                            <button type="submit" class="btn-publish-comment" style="padding: 6px 16px; font-size: 12px;">
                                                <span wire:loading.remove wire:target="submitReply">Répondre</span>
                                                <span wire:loading wire:target="submitReply">Envoi...</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        {{-- ═══ Réponses imbriquées ═══ --}}
                        @if($comment->replies->count() > 0)
                            <div class="mt-3 sm:mt-4 sm:ml-11">
                                <button type="button" @click="openReplies = !openReplies" 
                                        class="text-xs text-[#7b8ef8] hover:underline inline-flex items-center gap-1.5 cursor-pointer font-medium">
                                    <svg class="w-3 h-3 transition-transform duration-200" :class="openReplies ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <span>
                                        <span x-show="!openReplies">Afficher {{ $comment->replies->count() === 1 ? 'la réponse' : $comment->replies->count() . ' réponses' }}</span>
                                        <span x-show="openReplies">Masquer les réponses</span>
                                    </span>
                                </button>

                                <div x-show="openReplies" x-collapse class="comment-replies-list" style="margin-left: 0;">
                                    @foreach($comment->replies as $reply)
                                        @php
                                            $isOwnerReply = auth()->check() ? ($reply->user_id === auth()->id()) : ($reply->ip_hash === $currentIpHash && !$reply->user_id);
                                            $canManageReply = $isOwnerReply || $isAdmin;
                                            $isReplyLiked = in_array($reply->id, $userLikedCommentIds ?? []);
                                            $isReplyDisliked = in_array($reply->id, $userDislikedCommentIds ?? []);
                                        @endphp
                                        <div class="reply-item-card">
                                            <div class="comment-meta-row">
                                                <div class="comment-user-info">
                                                    <div class="comment-avatar-34" style="width: 28px; height: 28px; font-size: 11px;">
                                                        @if($reply->user && $reply->user->avatar)
                                                             <img src="{{ Storage::url($reply->user->avatar) }}" alt="{{ $reply->pseudo }}">
                                                        @else
                                                            <span>{{ strtoupper(substr($reply->pseudo ?: 'A', 0, 1)) }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="comment-user-text">
                                                        <span class="comment-pseudo" style="font-size: 12px;">{{ $reply->pseudo ?: 'Anonyme' }}</span>
                                                        <span class="comment-time">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($editingCommentId === $reply->id)
                                                <div class="sm:ml-9 mb-2">
                                                    <textarea wire:model="editingContent" class="comments-textarea" rows="2"></textarea>
                                                    @error('editingContent') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                                    <div class="flex gap-2 mt-2">
                                                        <button type="button" wire:click="updateComment" class="px-3 py-1 rounded-full bg-[#5b6ef5] text-white text-xs font-semibold cursor-pointer">Enregistrer</button>
                                                        <button type="button" wire:click="cancelEdit" class="px-3 py-1 rounded-full bg-[#202030] text-xs text-[#a0a0c0] hover:text-white cursor-pointer">Annuler</button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="comment-body-text reply-body-text" style="font-size: 13px; margin-bottom: 8px;">{!! $reply->rendered_content !!}</div>
                                            @endif

                                            <div class="comment-actions-bar reply-actions-bar">
                                                <button type="button" wire:click="toggleLike({{ $reply->id }})" class="comment-action-btn {{ $isReplyLiked ? 'active-like' : '' }}">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="{{ $isReplyLiked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                                    </svg>
                                                    <span>{{ $reply->likes_count ?: '' }}</span>
                                                </button>

                                                <button type="button" wire:click="toggleDislike({{ $reply->id }})" class="comment-action-btn {{ $isReplyDisliked ? 'active-dislike' : '' }}">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="{{ $isReplyDisliked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                                                    </svg>
                                                    <span>{{ $reply->dislikes_count ?: '' }}</span>
                                                </button>

                                                @auth
                                                    <button type="button" wire:click="setReplyTo({{ $comment->id }}, '{{ addslashes($reply->pseudo ?: 'Anonyme') }}')" class="comment-action-btn">
                                                        Répondre
                                                    </button>
                                                @else
                                                    <a href="{{ route('login') }}" class="comment-action-btn" title="Connectez-vous pour répondre">
                                                        Répondre
                                                    </a>
                                                @endauth

                                                @if($canManageReply)
                                                    <button type="button" wire:click="startEdit({{ $reply->id }})" class="comment-action-btn btn-edit">Modifier</button>
                                                    <button type="button" wire:click="deleteComment({{ $reply->id }})" onclick="confirm('Supprimer cette réponse ?') || event.stopImmediatePropagation()" class="comment-action-btn btn-delete text-red-400/80">Supprimer</button>
                                                @else
                                                    <button type="button" wire:click="report({{ $reply->id }})" onclick="confirm('Signaler cette réponse ?') || event.stopImmediatePropagation()" class="comment-report-btn">Signaler</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10 bg-[#1a1a22] rounded-xl" style="border: 1px solid #252535;">
                    <p class="text-[#60608a] text-sm">Soyez le premier à donner votre avis !</p>
                </div>
            @endforelse
        </div>

        {{-- ═══ Pagination ═══ --}}
        @if($comments && $comments->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $comments->links() }}
            </div>
        @endif
        @endauth
    </div>
</div>

<script>
function commentHelper() {
    return {
        attachedImage: @entangle('attachedImage'),
        wrapText(startTag, endTag) {
            const textarea = this.$refs.commentTextarea;
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end) || 'texte';
            const replacement = startTag + selectedText + endTag;
            
            textarea.value = text.substring(0, start) + replacement + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + startTag.length, start + startTag.length + selectedText.length);
            
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },
        wrapInlineText(startTag, endTag) {
            const textarea = this.$refs.inlineReplyTextarea;
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end) || 'texte';
            const replacement = startTag + selectedText + endTag;
            
            textarea.value = text.substring(0, start) + replacement + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + startTag.length, start + startTag.length + selectedText.length);
            
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },
        insertQuote() {
            this.wrapText('> ', '');
        },
        insertInlineQuote() {
            this.wrapInlineText('> ', '');
        },
        showImagePrompt() {
            const url = prompt('Entrez l\'URL de l\'image (https://...) :');
            if (url && url.startsWith('http')) {
                this.attachedImage = url;
            }
        }
    };
}
</script>