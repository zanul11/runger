@extends('layouts.gtr')

@section('content')

<!-- HERO -->
<header class="hero" id="home">
  <img class="hero-video" src="{{ asset('assets/gtr/gallery/ari.jpeg') }}" alt="Gerung Trail Run">
  <div class="hero-overlay"></div>
  <div class="hero-logos">
    <img src="{{ asset('assets/gtr/lobar.png') }}" alt="Pemkab Lombok Barat">
    <img src="{{ asset('assets/gtr/dispar.png') }}" alt="Dinas Pariwisata">
  </div>
  <div class="hero-inner wrap">
    <div class="hero-eye">1st Edition · Bukit Keteri Trail</div>
    <h1 class="hero-title">Gerung<br>Trail Run <span class="accent">2026.</span></h1>
    <div class="hero-meta">
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Minggu, 29 November 2026
      </span>
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Bukit Keteri, Gerung — Lombok Barat
      </span>
      <span class="hm">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4l5.5 10.5-3.2-1.6L12 16l-2.3-3.1-3.2 1.6z"/></svg>
        Kategori 7K &amp; 15K
      </span>
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
            <div class="cat-row"><span class="k">Elevation Gain</span><span class="v">{{ $cat->elevation_gain }}</span></div>
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
        <div class="eye">Event Overview</div>
        <h2>Gerung Trail Run 2026</h2>
        <p>
          Edisi perdana race trail yang digelar Runners Gerung di <strong style="color:#fff">Bukit Keteri</strong>,
          Gerung. Rute melintasi punggung bukit, hutan rendah, dan jalur pedesaan dengan panorama
          Lombok Barat saat matahari terbit.
        </p>
        <p>
          Dua kategori dirancang untuk semua level — dari pelari yang ingin mencoba trail
          pertamanya, sampai yang mencari tantangan elevasi & jarak.
        </p>
      </div>

      <div class="ov-photos">
        <div class="ov-photo big" style="background-image:url('{{ asset('assets/gtr/WhatsApp Image 2026-06-07 at 09.22.00.jpeg') }}')"></div>
        <div class="ov-photo" style="background-image:url('{{ asset('assets/gtr/WhatsApp Image 2026-06-06 at 13.37.28.jpeg') }}')"></div>
        <div class="ov-photo" style="background-image:url('{{ asset('assets/g-sunset.jpeg') }}')"></div>
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
@php
  $scenicFiles = glob(public_path('assets/gtr/scenic/*.{jpg,JPG,jpeg,JPEG,png,PNG}'), GLOB_BRACE) ?: [];
  sort($scenicFiles);
  $scenicLabels = ['Top Keteri', 'Downhill Keteri', 'Air Terjun Meledos', 'Tugu Gerung', 'Punggung Bukit', 'Night Trail', 'Jalur Desa', 'Finish Line'];
@endphp
@if(count($scenicFiles))
<section class="course-section" id="course">
  <div class="wrap">
    <div class="block-head"><h2>Our Scenic Course</h2></div>
    <div class="course-grid">
      @foreach($scenicFiles as $i => $path)
        @php $label = $scenicLabels[$i] ?? null; @endphp
        <div class="course-tile">
          <img src="{{ asset('assets/gtr/scenic/' . rawurlencode(basename($path))) }}" alt="{{ $label ?? 'Scenic course' }}" loading="lazy">
          @if($label)<span class="label">{{ $label }}</span>@endif
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

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
      <a class="contact-item" href="https://wa.me/" target="_blank" rel="noopener">
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
      <a class="contact-item" href="mailto:info@runnersgerung.id">
        <div class="ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div>
          <div class="lab">Email</div>
          <div class="val">info@runnersgerung.id</div>
        </div>
      </a>
    </div>
  </div>
</section>

@endsection
