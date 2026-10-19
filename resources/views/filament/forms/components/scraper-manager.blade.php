@php
    $manga = $getRecord();
    $sources = $manga ? $manga->scraperMangas()->with('scraperSource')->orderBy('priority', 'asc')->get() : collect();
@endphp

<div class="scraper-manager-container" id="scraper-manager-root" data-manga-id="{{ $manga?->id }}">
    <style>
        .scraper-manager-container {
            --accent: #6366f1;
            --accent-hover: #4f46e5;
            --border: rgba(255, 255, 255, 0.1);
            --bg-card: rgba(17, 24, 39, 0.6);
            --bg-card-hover: rgba(31, 41, 55, 0.7);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            font-family: inherit;
            margin-top: 4px;
        }

        .scraper-card-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .scraper-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 16px;
            transition: all 0.2s ease;
            position: relative;
            cursor: default;
        }

        .scraper-card.is-dragging {
            opacity: 0.4;
            border-style: dashed;
        }

        .scraper-card.drag-over {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .scraper-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .scraper-drag-handle {
            cursor: grab;
            color: #6b7280;
            padding: 2px 6px;
            margin-left: -6px;
            user-select: none;
            font-size: 16px;
            line-height: 1;
            border-radius: 4px;
        }

        .scraper-drag-handle:hover {
            color: #d1d5db;
            background: rgba(255, 255, 255, 0.05);
        }

        .scraper-title-group {
            flex: 1;
        }

        .scraper-site-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .scraper-site-url {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
            word-break: break-all;
            text-decoration: none;
        }

        .scraper-site-url:hover {
            text-decoration: underline;
            color: #cbd5e1;
        }

        .scraper-status-line {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            margin-top: 10px;
            color: var(--text-main);
        }

        .status-dot {
            font-size: 10px;
            line-height: 1;
        }

        .status-dot.dot-importing {
            color: #38bdf8;
            animation: pulse-dot 1.5s infinite;
        }

        .status-dot.dot-active {
            color: #10b981;
        }

        .status-dot.dot-annule {
            color: #f59e0b;
        }

        .status-dot.dot-paused {
            color: #94a3b8;
        }

        .status-dot.dot-error {
            color: #ef4444;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .scraper-progress-bar-container {
            width: 100%;
            height: 2px;
            background: var(--border);
            margin-top: 8px;
            border-radius: 1px;
            overflow: hidden;
            display: none;
        }

        .scraper-progress-bar-fill {
            height: 100%;
            background: var(--accent);
            width: 0%;
            transition: width 0.3s ease;
        }

        .scraper-actions-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .btn-scraper {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-scraper:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-scraper-cancel {
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.4);
            background: rgba(239, 68, 68, 0.1);
        }

        .btn-scraper-cancel:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }

        .btn-scraper-delete {
            color: #9ca3af;
            border-color: transparent;
            margin-left: auto;
        }

        .btn-scraper-delete:hover {
            color: #f87171;
            background: rgba(239, 68, 68, 0.1);
        }

        /* Formulaire d'ajout inline */
        .scraper-add-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            background: transparent;
            border: 1px dashed var(--border);
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            justify-content: center;
        }

        .scraper-add-trigger:hover {
            border-color: var(--accent);
            color: var(--text-main);
            background: rgba(99, 102, 241, 0.05);
        }

        .scraper-inline-form {
            display: none;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin-top: 10px;
        }

        .scraper-inline-form.is-open {
            display: block;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 640px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .scraper-input-field {
            width: 100%;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.2s ease;
        }

        .scraper-input-field:focus {
            border-color: var(--accent);
        }

        .scraper-form-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-import {
            background: var(--accent);
            color: #ffffff;
            border: 1px solid var(--accent);
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-submit-import:hover {
            background: var(--accent-hover);
        }

        .btn-cancel-form {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid transparent;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-cancel-form:hover {
            color: var(--text-main);
        }
    </style>

    {{-- Liste des sources existantes --}}
    <div class="scraper-card-list" id="scraper-cards-container">
        @forelse($sources as $sm)
            @php
                $status = $sm->status;
                $isImporting = in_array($status, ['importing', 'en cours d\'import']);
                $total = $sm->total_chapters_detected ?? 0;
                $current = $sm->current_scraping_chapter ?? '0';
                $last = $sm->last_chapter_scraped ?? '0';
                $percent = ($total > 0 && is_numeric($current)) ? min(100, max(1, round(((float)$current / $total) * 100))) : 0;
                $urlDisplay = preg_replace('#^https?://#', '', $sm->source_url);
            @endphp
            <div class="scraper-card"
                 id="scraper-card-{{ $sm->id }}"
                 data-id="{{ $sm->id }}"
                 data-status="{{ $status }}"
                 draggable="true">

                <div class="scraper-card-header">
                    <span class="scraper-drag-handle" title="Glisser pour changer l'ordre de priorité">⋮⋮</span>
                    <div class="scraper-title-group">
                        <div class="scraper-site-name">
                            <span>{{ $sm->scraperSource?->name ?: 'Source' }}</span>
                        </div>
                        <a href="{{ $sm->source_url }}" target="_blank" rel="noopener noreferrer" class="scraper-site-url">
                            {{ $urlDisplay }}
                        </a>
                    </div>
                </div>

                {{-- Ligne de statut --}}
                <div class="scraper-status-line">
                    <span class="status-dot dot-{{ $status === 'importing' ? 'importing' : ($status === 'active' ? 'active' : ($status === 'annulé' ? 'annule' : ($status === 'paused' ? 'paused' : 'error'))) }}">●</span>
                    <span class="status-text" id="status-text-{{ $sm->id }}">
                        @if($isImporting)
                            Import en cours — Chapitre {{ $current }} / {{ $total ?: '...' }}
                        @elseif($status === 'active')
                            Actif — Dernier import : Ch. {{ $last ?: '—' }} {{ $sm->last_checked_at ? $sm->last_checked_at->diffForHumans() : '' }}
                        @elseif($status === 'annulé')
                            Annulé — Arrêté au Ch. {{ $last ?: '0' }} / {{ $total ?: '...' }}
                        @elseif($status === 'paused')
                            En pause — Ch. {{ $last ?: '—' }} importé
                        @elseif($status === 'error')
                            <span style="color: #f87171;">Erreur — {{ $sm->last_error ?: 'Échec du scraping' }}</span>
                        @else
                            {{ ucfirst($status) }}
                        @endif
                    </span>
                </div>

                {{-- Barre de progression fine (2px) --}}
                <div class="scraper-progress-bar-container" id="progress-bar-{{ $sm->id }}" style="{{ $isImporting ? 'display:block;' : '' }}">
                    <div class="scraper-progress-bar-fill" id="progress-fill-{{ $sm->id }}" style="width: {{ $percent }}%;"></div>
                </div>

                {{-- Boutons d'actions par statut --}}
                <div class="scraper-actions-row" id="actions-row-{{ $sm->id }}">
                    @if($isImporting)
                        <button type="button" class="btn-scraper btn-scraper-cancel" onclick="cancelScraping({{ $sm->id }})">
                            ■ Annuler
                        </button>
                    @elseif($status === 'active')
                        <button type="button" class="btn-scraper" onclick="syncScraping({{ $sm->id }})">
                            ↻ Sync maintenant
                        </button>
                        <button type="button" class="btn-scraper" onclick="toggleScraping({{ $sm->id }})">
                            ⏸ Pause
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping({{ $sm->id }})" title="Supprimer la source">
                            ✕
                        </button>
                    @elseif($status === 'annulé')
                        @php $nextCh = is_numeric($last) ? (((float)$last) + 1) : null; @endphp
                        <button type="button" class="btn-scraper" onclick="syncScraping({{ $sm->id }})">
                            ↻ Reprendre {{ $nextCh ? "depuis Ch. {$nextCh}" : '' }}
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping({{ $sm->id }})" title="Supprimer la source">
                            ✕
                        </button>
                    @elseif($status === 'paused')
                        <button type="button" class="btn-scraper" onclick="toggleScraping({{ $sm->id }})">
                            ▶ Reprendre
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping({{ $sm->id }})" title="Supprimer la source">
                            ✕
                        </button>
                    @elseif($status === 'error')
                        <button type="button" class="btn-scraper" onclick="syncScraping({{ $sm->id }})">
                            ↻ Réessayer
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping({{ $sm->id }})" title="Supprimer la source">
                            ✕
                        </button>
                    @endif
                </div>

            </div>
        @empty
            <div id="no-sources-placeholder" style="color: var(--text-muted); font-size: 13px; font-style: italic; padding: 12px 0;">
                Aucune source configurée pour cette œuvre.
            </div>
        @endforelse
    </div>

    {{-- Déclencheur et formulaire inline d'ajout --}}
    @if($manga)
        <div>
            <button type="button" class="scraper-add-trigger" id="btn-open-add-form" onclick="toggleAddSourceForm(true)">
                + Ajouter une source
            </button>

            <div class="scraper-inline-form" id="scraper-inline-form">
                <form id="form-attach-source" onsubmit="submitAddSource(event)">
                    @csrf
                    <input type="hidden" name="manga_id" value="{{ $manga->id }}">
                    <div class="form-grid-2">
                        <div>
                            <input type="text"
                                   id="input-source-name"
                                   name="name"
                                   placeholder="Nom du site (ex: Poseidon, Asura...)"
                                   class="scraper-input-field">
                        </div>
                        <div>
                            <input type="url"
                                   id="input-source-url"
                                   name="source_url"
                                   placeholder="https://site-scan.fr/serie/nom-du-manga"
                                   required
                                   class="scraper-input-field">
                        </div>
                    </div>
                    <div class="scraper-form-buttons">
                        <button type="submit" class="btn-submit-import" id="btn-submit-source">
                            Lancer l'import
                        </button>
                        <button type="button" class="btn-cancel-form" onclick="toggleAddSourceForm(false)">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Script JavaScript Livewire / Fetch pour l'interactivité complète --}}
    <script>
        (function() {
            const root = document.getElementById('scraper-manager-root');
            if (!root) return;

            const csrfToken = '{{ csrf_token() }}';
            let pollTimer = null;

            // 1. Gestion de l'ouverture/fermeture du formulaire inline
            window.toggleAddSourceForm = function(open) {
                const form = document.getElementById('scraper-inline-form');
                const trigger = document.getElementById('btn-open-add-form');
                if (!form || !trigger) return;

                if (open) {
                    form.classList.add('is-open');
                    trigger.style.display = 'none';
                    document.getElementById('input-source-name')?.focus();
                } else {
                    form.classList.remove('is-open');
                    trigger.style.display = 'inline-flex';
                    document.getElementById('input-source-name').value = '';
                    document.getElementById('input-source-url').value = '';
                }
            };

            // 2. Soumission du formulaire d'ajout inline (sans rechargement)
            window.submitAddSource = async function(event) {
                event.preventDefault();
                const form = event.target;
                const submitBtn = document.getElementById('btn-submit-source');
                const mangaId = root.dataset.mangaId;
                const name = document.getElementById('input-source-name')?.value?.trim();
                const sourceUrl = document.getElementById('input-source-url')?.value?.trim();

                if (!sourceUrl) return;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Ajout...';

                try {
                    const res = await fetch('{{ route('admin.scraper.attach') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            manga_id: mangaId,
                            name: name,
                            source_url: sourceUrl
                        })
                    });

                    const data = await res.json();
                    if (data.success && data.scraperManga) {
                        const sm = data.scraperManga;
                        toggleAddSourceForm(false);

                        // Supprime le placeholder "Aucune source" si présent
                        document.getElementById('no-sources-placeholder')?.remove();

                        // Ajoute la carte dans la liste avec statut "Import en cours — Ch. 0 / ..."
                        const cardHtml = createCardHtml(sm);
                        document.getElementById('scraper-cards-container').insertAdjacentHTML('beforeend', cardHtml);

                        // Active la surveillance
                        startPolling();
                    } else {
                        alert(data.message || 'Erreur lors de l\'ajout.');
                    }
                } catch (e) {
                    console.error('Erreur attach scraper:', e);
                    alert('Une erreur réseau est survenue.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Lancer l\'import';
                }
            };

            // 3. Annuler l'import en cours
            window.cancelScraping = async function(id) {
                const statusText = document.getElementById('status-text-' + id);
                if (statusText) {
                    statusText.textContent = 'Arrêt en cours...';
                }

                try {
                    const res = await fetch('/admin/scraper/' + id + '/cancel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    // Interroge le statut immédiatement
                    setTimeout(() => fetchCardStatus(id), 600);
                } catch (e) {
                    console.error('Erreur annulation scraper:', e);
                }
            };

            // 4. Forcer la synchronisation ou reprendre
            window.syncScraping = async function(id) {
                const statusText = document.getElementById('status-text-' + id);
                if (statusText) {
                    statusText.textContent = 'Démarrage de l\'import...';
                }

                try {
                    await fetch('/admin/scraper/' + id + '/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    startPolling();
                    setTimeout(() => fetchCardStatus(id), 600);
                } catch (e) {
                    console.error('Erreur sync scraper:', e);
                }
            };

            // 5. Mettre en pause ou reprendre
            window.toggleScraping = async function(id) {
                try {
                    await fetch('/admin/scraper/' + id + '/toggle', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    setTimeout(() => fetchCardStatus(id), 300);
                } catch (e) {
                    console.error('Erreur toggle scraper:', e);
                }
            };

            // 6. Supprimer une source
            window.deleteScraping = async function(id) {
                if (!confirm('Supprimer cette source de scraping ?')) return;

                try {
                    const res = await fetch('/admin/scraper/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        const card = document.getElementById('scraper-card-' + id);
                        if (card) {
                            card.remove();
                        }
                        const remaining = document.querySelectorAll('.scraper-card');
                        if (remaining.length === 0) {
                            document.getElementById('scraper-cards-container').innerHTML =
                                '<div id="no-sources-placeholder" style="color: var(--text-muted); font-size: 13px; font-style: italic; padding: 12px 0;">Aucune source configurée pour cette œuvre.</div>';
                        }
                    }
                } catch (e) {
                    console.error('Erreur suppression scraper:', e);
                }
            };

            // 7. Génération de carte HTML
            function createCardHtml(sm) {
                const urlClean = (sm.source_url || '').replace(/^https?:\/\//, '');
                return `
                    <div class="scraper-card" id="scraper-card-${sm.id}" data-id="${sm.id}" data-status="${sm.status}" draggable="true">
                        <div class="scraper-card-header">
                            <span class="scraper-drag-handle" title="Glisser pour changer l'ordre de priorité">⋮⋮</span>
                            <div class="scraper-title-group">
                                <div class="scraper-site-name">
                                    <span>${sm.source_name || 'Source'}</span>
                                </div>
                                <a href="${sm.source_url}" target="_blank" rel="noopener noreferrer" class="scraper-site-url">
                                    ${urlClean}
                                </a>
                            </div>
                        </div>

                        <div class="scraper-status-line">
                            <span class="status-dot dot-importing">●</span>
                            <span class="status-text" id="status-text-${sm.id}">Import en cours — Ch. 0 / ...</span>
                        </div>

                        <div class="scraper-progress-bar-container" id="progress-bar-${sm.id}" style="display:block;">
                            <div class="scraper-progress-bar-fill" id="progress-fill-${sm.id}" style="width: 2%;"></div>
                        </div>

                        <div class="scraper-actions-row" id="actions-row-${sm.id}">
                            <button type="button" class="btn-scraper btn-scraper-cancel" onclick="cancelScraping(${sm.id})">
                                ■ Annuler
                            </button>
                        </div>
                    </div>
                `;
            }

            // 8. Polling AJAX toutes les 10 secondes
            async function fetchCardStatus(id) {
                try {
                    const res = await fetch('/admin/scraper/' + id + '/status', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    const card = document.getElementById('scraper-card-' + id);
                    if (!card) return;

                    card.dataset.status = data.status;

                    // MAJ du texte
                    const textEl = document.getElementById('status-text-' + id);
                    if (textEl) {
                        if (data.status === 'error') {
                            textEl.innerHTML = '<span style="color:#f87171;">' + (data.status_text || 'Erreur') + '</span>';
                        } else {
                            textEl.textContent = data.status_text;
                        }
                    }

                    // MAJ du voyant dot
                    const dotEl = card.querySelector('.status-dot');
                    if (dotEl) {
                        dotEl.className = 'status-dot dot-' + (data.status === 'importing' ? 'importing' : (data.status === 'active' ? 'active' : (data.status === 'annulé' ? 'annule' : (data.status === 'paused' ? 'paused' : 'error'))));
                    }

                    // MAJ de la barre de progression (2px)
                    const pBar = document.getElementById('progress-bar-' + id);
                    const pFill = document.getElementById('progress-fill-' + id);
                    if (pBar && pFill) {
                        if (data.is_importing) {
                            pBar.style.display = 'block';
                            pFill.style.width = (data.progress_percent || 5) + '%';
                        } else {
                            pBar.style.display = 'none';
                        }
                    }

                    // MAJ des boutons selon le statut exact demandé
                    const actionsRow = document.getElementById('actions-row-' + id);
                    if (actionsRow) {
                        actionsRow.innerHTML = renderButtonsForStatus(id, data);
                    }

                } catch (e) {
                    console.error('Erreur récupération statut scraper:', e);
                }
            }

            function renderButtonsForStatus(id, data) {
                if (data.is_importing) {
                    return `
                        <button type="button" class="btn-scraper btn-scraper-cancel" onclick="cancelScraping(${id})">
                            ■ Annuler
                        </button>
                    `;
                }

                if (data.status === 'active') {
                    return `
                        <button type="button" class="btn-scraper" onclick="syncScraping(${id})">
                            ↻ Sync maintenant
                        </button>
                        <button type="button" class="btn-scraper" onclick="toggleScraping(${id})">
                            ⏸ Pause
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping(${id})" title="Supprimer la source">
                            ✕
                        </button>
                    `;
                }

                if (data.status === 'annulé') {
                    const resumeLabel = data.next_chapter ? ('↻ Reprendre depuis Ch. ' + data.next_chapter) : '↻ Reprendre';
                    return `
                        <button type="button" class="btn-scraper" onclick="syncScraping(${id})">
                            ${resumeLabel}
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping(${id})" title="Supprimer la source">
                            ✕
                        </button>
                    `;
                }

                if (data.status === 'paused') {
                    return `
                        <button type="button" class="btn-scraper" onclick="toggleScraping(${id})">
                            ▶ Reprendre
                        </button>
                        <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping(${id})" title="Supprimer la source">
                            ✕
                        </button>
                    `;
                }

                // Error
                return `
                    <button type="button" class="btn-scraper" onclick="syncScraping(${id})">
                        ↻ Réessayer
                    </button>
                    <button type="button" class="btn-scraper btn-scraper-delete" onclick="deleteScraping(${id})" title="Supprimer la source">
                        ✕
                    </button>
                `;
            }

            function pollAllActiveSources() {
                const cards = document.querySelectorAll('.scraper-card');
                cards.forEach(c => {
                    const id = c.dataset.id;
                    if (id) fetchCardStatus(id);
                });
            }

            function startPolling() {
                if (pollTimer) clearInterval(pollTimer);
                // Toutes les 10 secondes selon la spécification
                pollTimer = setInterval(pollAllActiveSources, 10000);
            }

            startPolling();

            // 9. Glisser-déposer (Drag & Drop natif) pour réordonner les priorités
            const container = document.getElementById('scraper-cards-container');
            let draggedCard = null;

            container.addEventListener('dragstart', (e) => {
                const card = e.target.closest('.scraper-card');
                if (!card) return;
                draggedCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            container.addEventListener('dragend', (e) => {
                if (draggedCard) {
                    draggedCard.classList.remove('is-dragging');
                    draggedCard = null;
                }
                document.querySelectorAll('.scraper-card').forEach(c => c.classList.remove('drag-over'));
                saveNewOrder();
            });

            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const targetCard = e.target.closest('.scraper-card');
                if (targetCard && targetCard !== draggedCard) {
                    const bounding = targetCard.getBoundingClientRect();
                    const offset = e.clientY - bounding.top;
                    if (offset > bounding.height / 2) {
                        targetCard.after(draggedCard);
                    } else {
                        targetCard.before(draggedCard);
                    }
                }
            });

            async function saveNewOrder() {
                const cards = document.querySelectorAll('.scraper-card');
                const order = Array.from(cards).map(c => parseInt(c.dataset.id, 10));
                if (order.length === 0) return;

                const firstId = order[0];
                try {
                    await fetch('/admin/scraper/' + firstId + '/reorder', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order: order })
                    });
                } catch (e) {
                    console.error('Erreur enregistrement ordre scraper:', e);
                }
            }
        })();
    </script>
</div>
