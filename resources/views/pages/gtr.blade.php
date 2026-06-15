@extends('layouts.gtr')

@section('content')

<!-- HERO -->
<header class="hero" id="home">
  <img class="hero-video" src="{{ $gtrSetting?->header_url ?? asset('assets/gtr/gallery/ari.jpeg') }}" alt="Gerung Trail Run">
  <div class="hero-overlay"></div>
  <div class="hero-logos">
    <img src="{{ asset('assets/gtr/lobar.png') }}" alt="Pemkab Lombok Barat">
    <img src="{{ asset('assets/gtr/dispar.png') }}" alt="Dinas Pariwisata">
    <img src="{{ asset('assets/gtr/keteri.png') }}" alt="Bukit Keteri">
  </div>
  <div class="hero-inner wrap">
    <div class="hero-eye">{{ $gtrSetting->eyebrow ?? '1st Edition · Keteri Hill' }}</div>
    <h1 class="hero-title">{{ $gtrSetting->title ?? 'Gerung Trail Run 2026' }}</h1>
    <div class="hero-meta">
      @if($gtrSetting?->date_text ?? true)
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        {{ $gtrSetting->date_text ?? 'Minggu, 29 November 2026' }}
      </span>
      @endif
      @if($gtrSetting?->location_text ?? true)
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        {{ $gtrSetting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat' }}
      </span>
      @endif
      @if($gtrSetting?->categories_text ?? true)
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4l5.5 10.5-3.2-1.6L12 16l-2.3-3.1-3.2 1.6z"/></svg>
        {{ $gtrSetting->categories_text ?? 'Kategori 7K & 15K' }}
      </span>
      @endif
    </div>
    <div class="hero-cta">
      <a class="btn-primary" href="{{ auth('runner')->check() ? route('gtr.dashboard') : route('gtr.login') }}">Register Now <span class="arr">→</span></a>
      <a class="btn-ghost" href="#category">Lihat Kategori</a>
    </div>
  </div>
</header>

<!-- CATEGORIES -->
<section class="block" id="category" style="padding-top:56px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Race Categories</div>
      <h2>Choose Your Category</h2>
      <!-- <p>Dua kategori dengan karakter rute & tingkat elevasi berbeda. Pilih tantangan yang paling pas buat kamu.</p> -->
    </div>

    <div class="race-grid">
      @foreach($gtrCategories as $cat)
      <article class="cat-card" id="cat-{{ $cat->slug }}" style="background-image:url('{{ $cat->header_url }}')">
        <div class="cat-top">
          <span class="cat-tag-left"><span class="dot" style="background:{{ $cat->color }}"></span>{{ $cat->tag }}</span>
          <span class="cat-name-pill">{{ $cat->name }}</span>
        </div>
        <div class="cat-inner">
          <div class="cat-dist">{{ $cat->distance }}</div>
          @if($cat->description)<p class="cat-desc">{{ $cat->description }}</p>@endif
          <div class="cat-rows">
            <div class="cat-row"><span class="k">Elevation Gain</span><span class="v">{{ $cat->elevation_gain ?: '-' }}</span></div>
            <div class="cat-row"><span class="k">Cut-Off Time</span><span class="v">{{ $cat->cut_off_time }}</span></div>
            <div class="cat-row"><span class="k">Early Bird</span><span class="v">{{ $cat->early_bird_formatted }}</span></div>
            <div class="cat-row"><span class="k">Normal</span><span class="v strike">{{ $cat->normal_formatted }}</span></div>
          </div>
          <a class="cat-btn" href="{{ route('gtr.category', $cat->slug) }}">Detail Category <span class="arr">→</span></a>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>

<!-- OVERVIEW -->
<section class="overview" id="overview">
  <div class="wrap">
    <div class="ov-grid">
      <div class="ov-head">
        <div class="eye">{{ $gtrOverview->eyebrow ?? 'Event Overview' }}</div>
        <h2>{{ $gtrOverview->heading ?? 'Gerung Trail Run 2026' }}</h2>
        <p style="white-space:pre-line">{{ $gtrOverview->paragraph_1 ?? 'Edisi perdana race trail yang digelar Runners Gerung di Bukit Keteri, Gerung. Rute melintasi punggung bukit, hutan rendah, dan jalur pedesaan dengan panorama Lombok Barat saat matahari terbit.' }}</p>
        @if($gtrOverview?->paragraph_2 ?? true)
        <p style="white-space:pre-line">{{ $gtrOverview->paragraph_2 ?? 'Dua kategori dirancang untuk semua level — dari pelari yang ingin mencoba trail pertamanya, sampai yang mencari tantangan elevasi & jarak.' }}</p>
        @endif
      </div>

      <div class="ov-photos">
        <div class="ov-photo big" style="background-image:url('{{ $gtrOverview?->photo_main_url ?? asset('assets/gtr/WhatsApp Image 2026-06-07 at 09.22.00.jpeg') }}')"></div>
        <div class="ov-photo" style="background-image:url('{{ $gtrOverview?->photo_2_url ?? asset('assets/gtr/WhatsApp Image 2026-06-06 at 13.37.28.jpeg') }}')"></div>
        <div class="ov-photo" style="background-image:url('{{ $gtrOverview?->photo_3_url ?? asset('assets/g-sunset.jpeg') }}')"></div>
      </div>
    </div>

    <!-- INFO STRIP -->
    <div class="info-strip">
      <div class="info-block">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div>
          <div class="lab">Race Day</div>
          <div class="val">29 November 2026</div>
          <div class="sub">Minggu · Sunrise Start</div>
        </div>
      </div>

      <div class="info-block">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div>
          <div class="lab">Location</div>
          <div class="val">Bukit Keteri</div>
          <div class="sub">Gerung, Lombok Barat, NTB</div>
        </div>
      </div>

      <div class="countdown-block">
        <div class="cd-row">
          <div class="ic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="lab">Countdown To Race</div>
        </div>
        <div class="cd-grid">
          <div class="cd-cell"><div class="cd-num" id="cd-d">--</div><div class="cd-lab">Days</div></div>
          <div class="cd-cell"><div class="cd-num" id="cd-h">--</div><div class="cd-lab">Hrs</div></div>
          <div class="cd-cell"><div class="cd-num" id="cd-m">--</div><div class="cd-lab">Min</div></div>
          <div class="cd-cell"><div class="cd-num" id="cd-s">--</div><div class="cd-lab">Sec</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SUPPORTED BY -->
<section class="support-section" id="supported-by">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Supported By</div>
      <!-- <h2>Supported By</h2> -->
    </div>
    <div class="support-grid">
      <div class="support-item">
        <div class="support-logo"><img src="{{ asset('assets/gtr/lobar.png') }}" alt="Pemerintah Kabupaten Lombok Barat"></div>
        <div class="support-name">Pemerintah Kabupaten Lombok Barat</div>
      </div>
      <div class="support-item">
        <div class="support-logo"><img src="{{ asset('assets/gtr/dispar.png') }}" alt="Dinas Pariwisata, Ekonomi Kreatif, Pemuda dan Olahraga Lombok Barat"></div>
        <div class="support-name">Dinas Pariwisata, Ekonomi Kreatif, Pemuda dan Olahraga Lombok Barat</div>
      </div>
    </div>
  </div>
</section>

<!-- SPONSORS -->
<section class="sponsor-section">
  <div class="wrap sponsor-inner">
    <h2>Official Sponsors</h2>
    <div class="sponsor-row">
      <div class="sponsor-slot">Slot Sponsor</div>
      <div class="sponsor-slot">Slot Sponsor</div>
      <div class="sponsor-slot">Slot Sponsor</div>
      <div class="sponsor-slot">Slot Sponsor</div>
    </div>
    <div class="sponsor-cta">
      <h3>Interested in Becoming a Sponsor?</h3>
      <p>Jadilah bagian dari edisi perdana Gerung Trail Run dan terhubung dengan komunitas trail running yang berdedikasi di Lombok Barat.</p>
      <a class="btn-primary" href="{{ route('gtr') }}#contact">Contact Us <span class="arr">→</span></a>
    </div>
  </div>
</section>

<!-- SCENIC COURSE -->
<section class="course-section" id="course">
  <div class="wrap">
    <div class="block-head"><h2>Our Scenic Course</h2></div>
    @if(($gtrScenics ?? collect())->isNotEmpty())
    <div class="course-grid">
      @foreach($gtrScenics as $s)
        <div class="course-tile">
          <img src="{{ $s->image_url }}" alt="{{ $s->label ?? 'Scenic course' }}" loading="lazy">
          @if($s->label)<span class="label">{{ $s->label }}</span>@endif
        </div>
      @endforeach
    </div>
    @else
    <p style="text-align:center;color:var(--text-soft);font-size:14.5px">Foto rute akan segera diupdate.</p>
    @endif
  </div>
</section>

<!-- CONTACT -->
<section class="block" id="contact" style="padding-top:40px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Get In Touch</div>
      <h2>Contact</h2>
      <p>Hubungi kami untuk informasi sponsor, media partnership, atau pertanyaan seputar event.</p>
    </div>
    <div class="contact-grid">
      <a class="contact-item" href="https://www.instagram.com/runnersgerung/" target="_blank" rel="noopener">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/>
          </svg>
        </div>
        <div>
          <div class="lab">Instagram</div>
          <div class="val">@runnersgerung</div>
        </div>
      </a>
      <a class="contact-item" href="https://wa.me/6283129148945" target="_blank" rel="noopener">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
          </svg>
        </div>
        <div>
          <div class="lab">WhatsApp</div>
          <div class="val">Chat Panitia</div>
        </div>
      </a>
      <a class="contact-item" href="mailto:runnersgerung@gmail.com">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div>
          <div class="lab">Email</div>
          <div class="val">runnersgerung@gmail.com</div>
        </div>
      </a>
    </div>
  </div>
</section>

@endsection
