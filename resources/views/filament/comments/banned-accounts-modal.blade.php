<style>
.hs-ban-modal {
    display: flex;
    flex-direction: column;
    gap: 16px;
    font-family: inherit;
    color: var(--text-1, #f0f0f0);
    margin: -8px 0;
}
.hs-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}
.hs-stat-card {
    padding: 12px 16px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-surface, #141414);
    border: 1px solid var(--border, #2a2a2a);
}
.hs-stat-card.red {
    border-color: rgba(230, 57, 70, 0.4);
}
.hs-stat-card.amber {
    border-color: rgba(245, 158, 11, 0.4);
}
.hs-stat-icon-box {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hs-stat-icon-box.red {
    background: rgba(230, 57, 70, 0.15);
    color: var(--accent, #e63946);
}
.hs-stat-icon-box.amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
}
.hs-stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-2, #a0a0a0);
    margin: 0;
    line-height: 1.2;
}
.hs-stat-value {
    font-size: 20px;
    font-weight: 700;
    margin: 4px 0 0;
    line-height: 1.1;
    color: var(--text-1, #f0f0f0);
}
.hs-stat-value.red { color: var(--accent, #e63946); }
.hs-stat-value.amber { color: #fbbf24; }

.hs-section-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-2, #a0a0a0);
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.hs-list-container {
    background: var(--bg-surface, #141414);
    border: 1px solid var(--border, #2a2a2a);
    border-radius: 6px;
    overflow: hidden;
}
.hs-list-item {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid var(--border, #2a2a2a);
    transition: background 150ms ease;
}
.hs-list-item:last-child {
    border-bottom: none;
}
.hs-list-item:hover {
    background: var(--bg-raised, #1e1e1e);
}

.hs-item-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.hs-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 4px;
    background: var(--bg-raised, #1e1e1e);
    border: 1px solid var(--border, #2a2a2a);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    color: var(--text-1, #f0f0f0);
    flex-shrink: 0;
}
.hs-item-info {
    min-width: 0;
}
.hs-item-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.hs-user-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-1, #f0f0f0);
}
.hs-user-email {
    font-size: 12px;
    color: var(--text-3, #606060);
}
.hs-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
}
.hs-badge.red {
    background: rgba(230, 57, 70, 0.15);
    color: var(--accent, #e63946);
    border: 1px solid rgba(230, 57, 70, 0.3);
}
.hs-badge.amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.hs-badge.gray {
    background: var(--bg-raised, #1e1e1e);
    color: var(--text-2, #a0a0a0);
    border: 1px solid var(--border, #2a2a2a);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
}

.hs-item-meta {
    margin-top: 4px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 16px;
    font-size: 12px;
    color: var(--text-2, #a0a0a0);
}
.hs-item-meta strong {
    color: var(--text-1, #f0f0f0);
    font-weight: 600;
}

.hs-item-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.hs-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 150ms ease;
    border: none;
    line-height: 1.2;
}
.hs-btn-success {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.35);
}
.hs-btn-success:hover {
    background: rgba(34, 197, 94, 0.25);
    color: #86efac;
}
.hs-btn-neutral {
    background: var(--bg-raised, #1e1e1e);
    color: var(--text-1, #f0f0f0);
    border: 1px solid var(--border, #2a2a2a);
}
.hs-btn-neutral:hover {
    background: var(--border, #2a2a2a);
    color: #ffffff;
}

.hs-empty {
    padding: 24px;
    text-align: center;
    color: var(--text-3, #606060);
    font-size: 13px;
    background: var(--bg-surface, #141414);
    border: 1px dashed var(--border, #2a2a2a);
    border-radius: 6px;
}
</style>

<div class="hs-ban-modal">
    {{-- Résumé en compteurs --}}
    <div class="hs-stats-row">
        <div class="hs-stat-card red">
            <div class="hs-stat-icon-box red">
                <svg style="width: 18px; height: 18px; min-width: 18px;" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <p class="hs-stat-label">Comptes membres sanctionnés</p>
                <p class="hs-stat-value red">{{ $bannedUsers->count() }}</p>
            </div>
        </div>

        <div class="hs-stat-card amber">
            <div class="hs-stat-icon-box amber">
                <svg style="width: 18px; height: 18px; min-width: 18px;" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="hs-stat-label">Adresses IP bloquées</p>
                <p class="hs-stat-value amber">{{ $bannedIps->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Section 1 : Comptes membres --}}
    <div>
        <h4 class="hs-section-title">
            <svg style="width: 14px; height: 14px; min-width: 14px; color: #ef4444;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Comptes membres ({{ $bannedUsers->count() }})
        </h4>

        @if($bannedUsers->count() > 0)
            <div class="hs-list-container">
                @foreach($bannedUsers as $user)
                    <div class="hs-list-item">
                        <div class="hs-item-left">
                            <div class="hs-avatar">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="hs-item-info">
                                <div class="hs-item-header">
                                    <span class="hs-user-name">{{ $user->name }}</span>
                                    <span class="hs-user-email">({{ $user->email }})</span>
                                    @if($user->is_banned)
                                        <span class="hs-badge red flex items-center gap-1">
                                            <x-lucide name="ban" class="w-3 h-3 inline-block" /> Compte banni
                                        </span>
                                    @elseif($user->is_comment_banned)
                                        <span class="hs-badge amber flex items-center gap-1">
                                            <x-lucide name="shield-alert" class="w-3 h-3 inline-block" /> Banni des comm.
                                        </span>
                                    @endif
                                </div>
                                <div class="hs-item-meta">
                                    <span>Motif : <strong>{{ $user->ban_reason ?: ($user->comment_ban_reason ?: 'Non spécifié') }}</strong></span>
                                    <span>Date : <strong>{{ ($user->banned_at ?: $user->comment_banned_at)?->format('d/m/Y H:i') ?: '—' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="hs-item-actions">
                            <button type="button"
                                    wire:click="unbanUserById({{ $user->id }})"
                                    class="hs-btn hs-btn-success">
                                <svg style="width: 12px; height: 12px; min-width: 12px;" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Débannir
                            </button>
                            <a href="{{ route('profile.public', $user->username ?: ($user->name ?: $user->id)) }}"
                               target="_blank"
                               class="hs-btn hs-btn-neutral">
                                Profil public
                                <svg style="width: 10px; height: 10px; min-width: 10px;" width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                Aucun compte utilisateur membre n'est actuellement sanctionné.
            </div>
        @endif
    </div>

    {{-- Section 2 : Adresses IP --}}
    <div>
        <h4 class="hs-section-title">
            <svg style="width: 14px; height: 14px; min-width: 14px; color: #f59e0b;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Adresses IP sous restriction ({{ $bannedIps->count() }})
        </h4>

        @if($bannedIps->count() > 0)
            <div class="hs-list-container">
                @foreach($bannedIps as $ip)
                    <div class="hs-list-item">
                        <div class="hs-item-left">
                            <div class="hs-item-info">
                                <div class="hs-item-header">
                                    <span class="hs-badge gray">{{ substr($ip->ip_hash, 0, 20) }}...</span>
                                    <span class="hs-badge {{ $ip->ban_type === 'all' ? 'red' : 'amber' }} inline-flex items-center gap-1">
                                        @if($ip->ban_type === 'all')
                                            <x-lucide name="ban" class="w-3 h-3 inline-block" /> Accès total bloqué
                                        @else
                                            <x-lucide name="shield-alert" class="w-3 h-3 inline-block" /> Commentaires bloqués
                                        @endif
                                    </span>
                                </div>
                                <div class="hs-item-meta">
                                    <span>Motif : <strong>{{ $ip->reason ?: 'Non spécifié' }}</strong></span>
                                    <span>Date : <strong>{{ $ip->created_at?->format('d/m/Y H:i') }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="hs-item-actions">
                            <button type="button"
                                    wire:click="unbanIpById({{ $ip->id }})"
                                    class="hs-btn hs-btn-success">
                                <svg style="width: 12px; height: 12px; min-width: 12px;" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Débloquer IP
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                Aucune adresse IP n'est actuellement sous restriction.
            </div>
        @endif
    </div>
</div>
