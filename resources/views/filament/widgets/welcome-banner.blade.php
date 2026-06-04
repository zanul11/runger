@php
    $now = \Carbon\Carbon::now('Asia/Makassar')->locale('id');
    $hour = (int) $now->format('H');
    $greeting = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $name = filament()->auth()->user()?->name ?? 'Admin';
@endphp

<div class="rg-banner">
    <div class="rg-banner-blobs" aria-hidden="true">
        <span class="rg-blob rg-blob-1"></span>
        <span class="rg-blob rg-blob-2"></span>
        <span class="rg-blob rg-blob-3"></span>
    </div>
    <div class="rg-banner-main">
        <p class="rg-banner-eye">{{ $now->translatedFormat('l, d F Y') }} · WITA</p>
        <h2 class="rg-banner-title">{{ $greeting }}, {{ $name }} 👋</h2>
        <p class="rg-banner-sub">Lari Bareng, Sehat Bareng. Kelola komunitas Runger &amp; acara GTR dari sini.</p>
    </div>
</div>

<style>
    .rg-banner{
        position:relative;overflow:hidden;border-radius:1rem;padding:2rem 1.75rem;
        background:
            radial-gradient(640px 320px at 92% -25%, rgba(226,240,84,.32), transparent 62%),
            radial-gradient(520px 340px at 4% 125%, rgba(45,212,191,.34), transparent 62%),
            radial-gradient(560px 360px at 68% 135%, rgba(168,85,247,.32), transparent 60%),
            linear-gradient(125deg, #2447C9 0%, #2A2F8F 52%, #0F2680 100%);
        color:#F4F1EA;box-shadow:0 18px 50px rgba(10,15,44,.4);
    }
    /* faint grid texture */
    .rg-banner::before{
        content:'';position:absolute;inset:0;pointer-events:none;z-index:0;
        background-image:
            linear-gradient(transparent 96%, rgba(255,255,255,.07) 96%),
            linear-gradient(90deg, transparent 96%, rgba(255,255,255,.07) 96%);
        background-size:38px 38px;
        -webkit-mask:radial-gradient(circle at 80% 25%, #000 8%, transparent 72%);
                mask:radial-gradient(circle at 80% 25%, #000 8%, transparent 72%);
    }

    /* colorful floating blobs */
    .rg-banner-blobs{position:absolute;inset:0;z-index:0;pointer-events:none}
    .rg-blob{position:absolute;border-radius:50%;filter:blur(34px);opacity:.55;mix-blend-mode:screen}
    .rg-blob-1{width:240px;height:240px;top:-90px;right:8%;background:#E2F054;animation:rgFloat 9s ease-in-out infinite}
    .rg-blob-2{width:220px;height:220px;bottom:-110px;left:14%;background:#2DD4BF;animation:rgFloat 11s ease-in-out infinite reverse}
    .rg-blob-3{width:200px;height:200px;bottom:-90px;right:26%;background:#F472B6;animation:rgFloat 13s ease-in-out infinite}
    @keyframes rgFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(0,-16px)}}

    .rg-banner-main{position:relative;z-index:1}
    .rg-banner-eye{
        font-family:ui-monospace,'JetBrains Mono',monospace;font-size:.7rem;letter-spacing:.18em;
        text-transform:uppercase;color:#E2F054;opacity:.95;margin-bottom:.5rem;
    }
    .rg-banner-title{font-size:1.6rem;line-height:1.15;font-weight:800;margin:0 0 .4rem;letter-spacing:-.01em}
    .rg-banner-sub{font-size:.9rem;opacity:.85;max-width:48ch;margin:0}

    @media (min-width:768px){
        .rg-banner{padding:2.6rem 2.4rem}
        .rg-banner-title{font-size:2.05rem}
    }
    @media (prefers-reduced-motion:reduce){
        .rg-blob{animation:none}
    }
</style>
