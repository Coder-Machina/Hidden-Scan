{{-- ═══ SECTION SCRAPING CHAPITRES ═══ --}}
<div class="form-section">
  <div class="form-section__header">
    <span class="form-section__icon">⚡</span>
    <div>
      <h3 class="form-section__title">Source des Chapitres</h3>
      <p class="form-section__desc">
        Lier une URL externe pour importer les chapitres automatiquement.
      </p>
    </div>
  </div>

  <div class="form-section__body">

    {{-- Sélecteur de source --}}
    <div class="form-field">
      <label class="form-label">Site source</label>
      <select name="scraper_source_id" class="form-select">
        <option value="">— Aucune source (import manuel) —</option>
        @foreach($scraperSources as $source)
          <option value="{{ $source->id }}"
            {{ old('scraper_source_id',
               $manga->scraperManga->scraper_source_id ?? '')
               == $source->id ? 'selected' : '' }}>
            {{ ucfirst($source->name) }}
          </option>
        @endforeach
      </select>
      <p class="form-hint">
        Sélectionne le site depuis lequel importer les chapitres.
      </p>
    </div>

    {{-- URL du manga sur la source --}}
    <div class="form-field" id="scraper-url-field"
         style="{{ old('scraper_source_id',
                  $manga->scraperManga->scraper_source_id ?? '')
                  ? '' : 'display:none' }}">
      <label class="form-label">URL du manga sur ce site</label>
      <input type="url"
             name="scraper_source_url"
             class="form-input"
             placeholder="https://poseidon-scan.net/manga/solo-leveling"
             value="{{ old('scraper_source_url',
                      $manga->scraperManga->source_url ?? '') }}">
      <p class="form-hint">
        Colle l'URL exacte de la page manga sur le site source.
        Les chapitres seront importés automatiquement en arrière-plan.
      </p>
    </div>

    {{-- Statut scraping si modification --}}
    @isset($manga->scraperManga)
      <div class="scraper-status">
        <div class="scraper-status__row">
          <span>Statut :</span>
          <span class="badge badge--{{ $manga->scraperManga->status }}">
            {{ ucfirst($manga->scraperManga->status) }}
          </span>
        </div>
        @if($manga->scraperManga->last_chapter_scraped)
          <div class="scraper-status__row">
            <span>Dernier chapitre importé :</span>
            <span>{{ $manga->scraperManga->last_chapter_scraped }}</span>
          </div>
        @endif
        @if($manga->scraperManga->last_checked_at)
          <div class="scraper-status__row">
            <span>Dernière vérification :</span>
            <span>
              {{ $manga->scraperManga->last_checked_at->diffForHumans() }}
            </span>
          </div>
        @endif
        @if($manga->scraperManga->last_error)
          <div class="scraper-status__error">
            ⚠️ {{ $manga->scraperManga->last_error }}
          </div>
        @endif
        {{-- Bouton sync immédiat --}}
        <form method="POST"
              action="{{ route('admin.scraper.sync',
                         $manga->scraperManga) }}">
          @csrf
          <button type="submit" class="btn btn--ghost btn--sm">
            ↻ Vérifier maintenant
          </button>
        </form>
      </div>
    @endisset

  </div>
</div>

{{-- JS : afficher/cacher le champ URL selon la source choisie --}}
<script>
document.querySelector('[name="scraper_source_id"]')
  ?.addEventListener('change', function() {
    const field = document.getElementById('scraper-url-field');
    if (field) {
      field.style.display = this.value ? 'block' : 'none';
    }
  });
</script>
