{{-- Footer slide: siluet bukit + tanggal/lokasi + nomor halaman (diisi JS). --}}
<div class="s-foot">
  <svg class="s-ridge" viewBox="0 0 1080 190" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0 120 L90 78 L170 104 L260 40 L340 92 L420 66 L520 118 L610 52 L700 96 L790 30 L880 88 L960 62 L1080 100 L1080 190 L0 190 Z" fill="rgba(229,57,53,.16)"/>
    <path d="M0 150 L110 112 L200 136 L300 92 L390 128 L480 104 L590 146 L680 100 L780 134 L870 96 L980 130 L1080 112 L1080 190 L0 190 Z" fill="#111"/>
    <path d="M0 150 L110 112 L200 136 L300 92 L390 128 L480 104 L590 146 L680 100 L780 134 L870 96 L980 130 L1080 112" fill="none" stroke="#E53935" stroke-width="3" stroke-linejoin="round"/>
  </svg>
  <div class="s-foot-row">
    <span class="s-foot-txt">{{ collect([$g['event']['date'], 'Race Guide'])->filter()->implode('  ·  ') }}</span>
    <span class="s-pg"><b class="pg-n">01</b> / <span class="pg-t">01</span></span>
  </div>
</div>
