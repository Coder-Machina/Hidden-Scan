<style>
.hs-ban-modal {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    font-family: inherit;
    color: #e4e4e7;
    margin: -0.5rem 0;
}
.hs-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}
.hs-stat-card {
    padding: 0.875rem 1rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.hs-stat-card.red {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.25);
}
.hs-stat-card.amber {
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.25);
}
.hs-stat-icon-box {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hs-stat-icon-box.red {
    background: rgba(239, 68, 68, 0.18);
    color: #f87171;
}
.hs-stat-icon-box.amber {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
}
.hs-stat-label {
    font-size: 0.75rem;
    color: #a1a1aa;
    margin: 0;
    line-height: 1.2;
}
.hs-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0.2rem 0 0;
    line-height: 1.1;
}
.hs-stat-value.red { color: #f87171; }
.hs-stat-value.amber { color: #fbbf24; }

.hs-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #a1a1aa;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.hs-list-container {
    background: #18181b;
    border: 1px solid #27272a;
    border-radius: 0.75rem;
    overflow: hidden;
}
.hs-list-item {
    padding: 0.875rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid #27272a;
    transition: background 0.15s ease;
}
.hs-list-item:last-child {
    border-bottom: none;
}
.hs-list-item:hover {
    background: #1f1f23;
}

.hs-item-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    min-width: 0;
}
.hs-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 9999px;
    background: #27272a;
    border: 1px solid #3f3f46;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    color: #f4f4f5;
    flex-shrink: 0;
}
.hs-item-info {
    min-width: 0;
}
.hs-item-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.hs-user-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: #fafafa;
}
.hs-user-email {
    font-size: 0.75rem;
    color: #71717a;
}
.hs-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
    line-height: 1.3;
}
.hs-badge.red {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.35);
}
.hs-badge.amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.35);
}
.hs-badge.gray {
    background: #27272a;
    color: #d4d4d8;
    border: 1px solid #3f3f46;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
}

.hs-item-meta {
    margin-top: 0.3rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1.25rem;
    font-size: 0.75rem;
    color: #a1a1aa;
}
.hs-item-meta strong {
    color: #e4e4e7;
    font-weight: 600;
}

.hs-item-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}
.hs-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    border: none;
    line-height: 1.2;
}
.hs-btn-success {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
.hs-btn-success:hover {
    background: rgba(16, 185, 129, 0.28);
    color: #6ee7b7;
}
.hs-btn-neutral {
    background: #27272a;
    color: #e4e4e7;
    border: 1px solid #3f3f46;
}
.hs-btn-neutral:hover {
    background: #3f3f46;
    color: #ffffff;
}

.hs-empty {
    padding: 1.5rem;
    text-align: center;
    color: #71717a;
    font-size: 0.8125rem;
    background: #18181b;
    border: 1px dashed #27272a;
    border-radius: 0.75rem;
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
                                        <span class="hs-badge red">
                                            🚫 Compte banni
                                        </span>
                                    @elseif($user->is_comment_banned)
                                        <span class="hs-badge amber">
                                            ⛔ Banni des comm.
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
                            <a href="{{ route('filament.admin.resources.users.edit', ['record' => $user->id]) }}"
                               target="_blank"
                               class="hs-btn hs-btn-neutral">
                                Profil
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
                                    <span class="hs-badge {{ $ip->ban_type === 'all' ? 'red' : 'amber' }}">
                                        {{ $ip->ban_type === 'all' ? '🚫 Accès total bloqué' : '⛔ Commentaires bloqués' }}
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
