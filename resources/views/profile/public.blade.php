<x-layouts.public :title="$user->name . ($user->username ? ' (@' . $user->username . ')' : '') . ' — Hidden Scan'">
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

{{-- HEADER PROFIL --}}
<div class="profile-header">
  <div class="profile-header__inner">

    {{-- Avatar --}}
    <div class="profile-avatar">
      @if($user->avatar_url)
        <img src="{{ $user->avatar_url }}" alt="{{ $user->username ?: $user->name }}">
      @else
        <div class="profile-avatar__placeholder">
          {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
      @endif
    </div>

    {{-- Infos --}}
    <div class="profile-info">
      <h1 class="profile-info__name">{{ $user->name }}</h1>
      <p class="profile-info__username">{{ '@' . ($user->username ?: \Illuminate\Support\Str::slug($user->name, '')) }}</p>
      @if($user->bio)
        <p class="profile-info__bio">{{ $user->bio }}</p>
      @endif
      <p class="profile-info__joined">
        Membre depuis {{ $stats['membre_depuis'] }}
      </p>
    </div>

    {{-- Bouton suivre si connecté et pas soi-même --}}
    @auth
      @if(auth()->id() !== $user->id)
        <button class="btn btn--ghost profile-follow-btn">
          + Suivre
        </button>
      @endif
    @endauth

  </div>
</div>

{{-- STATS --}}
<div class="profile-stats">
  <div class="profile-stats__inner">
    <div class="stat-card">
      <span class="stat-card__value">{{ $stats['mangas_lus'] }}</span>
      <span class="stat-card__label">Mangas lus</span>
    </div>
    <div class="stat-card">
      <span class="stat-card__value">{{ $stats['chapitres_lus'] }}</span>
      <span class="stat-card__label">Chapitres lus</span>
    </div>
    <div class="stat-card">
      <span class="stat-card__value">{{ $stats['favoris'] }}</span>
      <span class="stat-card__label">Favoris</span>
    </div>
  </div>
</div>

{{-- FAVORIS --}}
@if($favoris->count() > 0)
<section class="profile-section">
  <h2 class="profile-section__title">Favoris</h2>
  <div class="manga-grid">
    @foreach($favoris as $fav)
      <a href="{{ route('manga.show', $fav->manga) }}" class="manga-card"
         data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
        <div class="manga-card__cover">
          <img src="{{ $fav->manga->cover_url }}"
               alt="{{ $fav->manga->title }}"
               loading="lazy">
        </div>
        <div class="manga-card__info">
          <p class="manga-card__title">{{ $fav->manga->title }}</p>
        </div>
      </a>
    @endforeach
  </div>
</section>
@endif

{{-- RÉCEMMENT LUS --}}
@if($recents->count() > 0)
<section class="profile-section">
  <h2 class="profile-section__title">Récemment lus</h2>
  <div class="manga-grid">
    @foreach($recents as $item)
      <a href="{{ route('manga.show', $item->manga) }}" class="manga-card"
         data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
        <div class="manga-card__cover">
          <img src="{{ $item->manga->cover_url }}"
               alt="{{ $item->manga->title }}"
               loading="lazy">
        </div>
        <div class="manga-card__info">
          <p class="manga-card__title">{{ $item->manga->title }}</p>
        </div>
      </a>
    @endforeach
  </div>
</section>
@endif

{{-- PROFIL PRIVÉ --}}
@if(!$user->profile_public)
  <div class="profile-private">
    <p>Ce profil est privé.</p>
  </div>
@endif
</div>
</x-layouts.public>
