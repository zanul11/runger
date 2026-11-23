{{--
  Peta admin Sign & Marshal — edit langsung di peta:
  - klik rute          → modal "Tambah titik" (KM terisi otomatis)
  - klik penanda       → modal edit (KM, arah, warna, keterangan / tim, jumlah) + hapus
  - geser penanda      → menempel ke rute, KM ikut berubah
  Setiap perubahan langsung disimpan lewat $wire.quickSave() (EditCrewPoints). Data tetap di
  data.crew_signs / data.crew_marshals sehingga tabel di bawah ikut berubah.
  KM diskalakan ke panjang profil elevasi resmi (sama dengan Course Map & /crew/peta).
--}}
@php
  $route = $record?->route_points ?? [];
  $ep = $record?->elevation_profile ?? [];
  $epMax = count($ep) ? (float) end($ep)[0] : null;
@endphp

@if(empty($route))
  <div style="padding:14px 16px;border-radius:12px;background:rgba(245,158,11,.1);color:#b45309;font-size:13px">
    Kategori ini belum punya rute GPX — upload GPX di menu GTR → Kategori dulu. KM tetap bisa diisi manual di tabel.
  </div>
@else
<style>
  .cpk{--bg:#fff;--fg:#18181b;--mut:#71717a;--line:rgba(0,0,0,.12);--soft:#f4f4f5;--acc:#cdee4b;--ink:#141414}
  .dark .cpk{--bg:#18181b;--fg:#f4f4f5;--mut:#a1a1aa;--line:rgba(255,255,255,.14);--soft:#27272a}
  .cpk-bar{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;margin-top:10px;font-size:13px;color:var(--mut)}
  .cpk-bar b{color:var(--fg)}
  .cpk-st{margin-left:auto;font-weight:700}
  .cpk-st.ok{color:#16a34a}.cpk-st.err{color:#dc2626}
  .cpk-back{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:16px;background:rgba(0,0,0,.55)}
  .cpk-win{width:min(460px,100%);max-height:calc(100vh - 32px);overflow:auto;border-radius:16px;background:var(--bg);color:var(--fg);box-shadow:0 20px 50px rgba(0,0,0,.4);font-size:14px}
  .cpk-hd{display:flex;align-items:center;gap:10px;padding:16px 18px 6px}
  .cpk-hd h3{margin:0;font-size:17px;font-weight:800;flex:1}
  .cpk-x{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;color:var(--mut)}
  .cpk-x:hover{background:var(--soft)}
  .cpk-bd{display:grid;gap:14px;padding:8px 18px 16px}
  .cpk-lab{display:block;margin-bottom:6px;font-size:12px;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.06em}
  .cpk-in{width:100%;padding:9px 11px;border-radius:9px;border:1px solid var(--line);background:transparent;color:var(--fg);font:inherit}
  .cpk-in:focus{outline:2px solid var(--acc);outline-offset:0;border-color:transparent}
  .cpk-row{display:flex;gap:8px;align-items:center}
  .cpk-seg{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:4px;border-radius:11px;background:var(--soft)}
  .cpk-seg button{padding:8px;border-radius:8px;font-weight:800;color:var(--mut)}
  .cpk-seg button.on{background:var(--ink);color:#fff}
  .cpk-dirs{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
  .cpk-dir{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 4px;border-radius:10px;border:1.5px solid var(--line);font-size:12px;font-weight:700}
  .cpk-dir.on{border-color:var(--fg);background:var(--soft)}
  .cpk-dir i{display:grid;place-items:center;width:28px;height:28px;border-radius:8px;color:#fff;background:var(--c)}
  .cpk-cols{display:flex;gap:8px;flex-wrap:wrap}
  .cpk-col{display:flex;align-items:center;gap:7px;padding:7px 12px 7px 8px;border-radius:999px;border:1.5px solid var(--line);font-weight:700}
  .cpk-col.on{border-color:var(--fg);background:var(--soft)}
  .cpk-col span{width:16px;height:16px;border-radius:5px;background:var(--c)}
  .cpk-chip{padding:6px 12px;border-radius:999px;border:1.5px solid var(--line);font-weight:700}
  .cpk-chip.on{border-color:var(--fg);background:var(--soft)}
  .cpk-step{width:36px;height:36px;border-radius:9px;border:1px solid var(--line);font-size:18px;font-weight:800}
  .cpk-ft{display:flex;align-items:center;gap:8px;padding:12px 18px 16px;border-top:1px solid var(--line)}
  .cpk-btn{padding:9px 16px;border-radius:9px;font-weight:800}
  .cpk-btn.pri{background:var(--acc);color:#141414}
  .cpk-btn.gh{color:var(--fg);border:1px solid var(--line)}
  .cpk-btn.del{color:#dc2626;margin-right:auto}
  .cpk-btn.del:hover{background:rgba(220,38,38,.1)}
  .cpk-err{padding:8px 10px;border-radius:8px;background:rgba(220,38,38,.1);color:#dc2626;font-size:13px;font-weight:600}
  .cpk-hint{font-size:12px;color:var(--mut)}
</style>
<script>
window.crewPicker = window.crewPicker || function (cfg) {
  const ARROW = d => {
    if (! d) return '<b style="font:900 12px sans-serif">?</b>';
    if (d.rotate === null) return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>';
    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" style="transform:rotate(' + d.rotate + 'deg)"><path d="M12 20V4M5 11l7-7 7 7"/></svg>';
  };
  const esc = v => { const e = document.createElement('div'); e.textContent = v == null ? '' : String(v); return e.innerHTML; };
  const uid = () => (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
    : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); });
  const PATH = { sign: 'data.crew_signs', marshal: 'data.crew_marshals' };
  // objek Leaflet & geometri di luar state Alpine (tidak dibuat reaktif)
  const route = cfg.route;
  let map = null, layer = null, ghost = null, cum = [], total = 0;
  // $wire diambil sekali dari elemen utama: method yang dipanggil dari modal (x-teleport ke <body>)
  // akan me-resolve $wire dari elemen modal yang sudah lepas setelah ditutup → data terbaca kosong.
  let wire = null;

  return {
    ...cfg,
    modal: null, status: '', statusCls: '', busy: false,
    last: { kind: 'sign', direction: 'straight', color: 'red', team: 'Basecamp' },

    init() {
      wire = this.$wire;
      const go = () => this.setup();
      if (window.L) return go();
      if (! document.getElementById('lf-css')) {
        const l = document.createElement('link');
        l.id = 'lf-css'; l.rel = 'stylesheet'; l.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(l);
      }
      let s = document.getElementById('lf-js');
      if (! s) {
        s = document.createElement('script');
        s.id = 'lf-js'; s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        document.head.appendChild(s);
      }
      s.addEventListener('load', go);
    },

    setup() {
      const R = 6371, rad = d => d * Math.PI / 180;
      cum = [0];
      for (let i = 1; i < route.length; i++) {
        const [a1, o1] = route[i - 1], [a2, o2] = route[i];
        const h = Math.sin(rad(a2 - a1) / 2) ** 2 + Math.cos(rad(a1)) * Math.cos(rad(a2)) * Math.sin(rad(o2 - o1) / 2) ** 2;
        cum[i] = cum[i - 1] + 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
      }
      total = cum[cum.length - 1] || 1;
      if (! this.epMax) this.epMax = total;

      map = L.map(this.$refs.map, { zoomSnap: .25 });
      const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
      const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
      sat.addTo(map);
      L.control.layers({ 'Satelit': sat, 'Peta': osm }, null, { collapsed: false }).addTo(map);
      L.polyline(route, { color: '#fff', weight: 9, interactive: false }).addTo(map);
      const line = L.polyline(route, { color: this.color, weight: 5 }).addTo(map);
      map.fitBounds(line.getBounds(), { padding: [20, 20] });
      for (let k = 1; k < this.epMax - .3; k++) {
        L.marker(this.kmToLatLng(k), { interactive: false, icon: L.divIcon({ className: '', iconSize: null,
          html: '<div style="transform:translate(-50%,-50%);min-width:18px;height:18px;padding:0 3px;border-radius:999px;background:#fff;color:#111;border:1.5px solid #111;font:800 10px sans-serif;display:grid;place-items:center">' + k + '</div>' }) }).addTo(map);
      }
      layer = L.layerGroup().addTo(map);
      const add = e => { const s = this.snap(e.latlng); this.openAdd(s.km); };
      map.on('click', add);
      line.on('click', e => { L.DomEvent.stopPropagation(e); add(e); });

      this.draw();
      wire.$watch('data.crew_signs', () => this.draw());
      wire.$watch('data.crew_marshals', () => this.draw());
    },

    // titik terdekat di rute → KM (skala profil) + koordinat
    snap(latlng) {
      const p = map.latLngToLayerPoint(latlng);
      let best = { d: Infinity, km: 0, ll: null };
      for (let i = 1; i < route.length; i++) {
        const a = map.latLngToLayerPoint(route[i - 1]), b = map.latLngToLayerPoint(route[i]);
        const dx = b.x - a.x, dy = b.y - a.y, len = dx * dx + dy * dy;
        const t = len ? Math.max(0, Math.min(1, ((p.x - a.x) * dx + (p.y - a.y) * dy) / len)) : 0;
        const x = a.x + t * dx, y = a.y + t * dy, d = Math.hypot(p.x - x, p.y - y);
        if (d < best.d) best = { d, km: cum[i - 1] + (cum[i] - cum[i - 1]) * t, ll: map.layerPointToLatLng(L.point(x, y)) };
      }
      return { km: Math.round(best.km / total * this.epMax * 100) / 100, ll: best.ll };
    },
    kmToLatLng(km) {
      const k = Math.max(0, Math.min(total, km / this.epMax * total));
      let j = 1; while (j < cum.length - 1 && cum[j] < k) j++;
      const seg = cum[j] - cum[j - 1], t = seg ? (k - cum[j - 1]) / seg : 0;
      const a = route[j - 1], b = route[j];
      return [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t];
    },
    showGhost(km) {
      if (ghost) { map.removeLayer(ghost); ghost = null; }
      if (km === '' || km == null || isNaN(km) || ! map) return;
      ghost = L.circleMarker(this.kmToLatLng(Number(km)), { radius: 11, color: '#cdee4b', weight: 4, fillColor: '#111', fillOpacity: .9, interactive: false }).addTo(map);
    },

    read(kind) {
      const v = JSON.parse(JSON.stringify(wire.$get(PATH[kind]) || {}));
      return Array.isArray(v) ? Object.assign({}, v) : v;
    },
    valid: p => p && p.km !== '' && p.km != null && ! isNaN(p.km),
    arrow(dir) { return ARROW(this.dirs[dir]); },

    draw() {
      if (! layer) return;
      layer.clearLayers();
      const signs = Object.entries(this.read('sign')).filter(([, p]) => this.valid(p));
      const signKm = new Set(signs.map(([, s]) => Number(s.km).toFixed(2)));
      const drag = (m, kind, key) => {
        m.on('click', () => this.openEdit(kind, key));
        m.on('drag', e => { const s = this.snap(e.target.getLatLng()); this.status = 'KM ' + s.km.toFixed(2); this.statusCls = ''; this.showGhost(s.km); });
        m.on('dragend', e => {
          const s = this.snap(e.target.getLatLng());
          this.showGhost(null);
          const cur = this.read(kind);
          if (! cur[key]) return this.draw();
          cur[key].km = s.km;
          wire.$set(PATH[kind], cur, false);
          this.persist();
        });
      };
      signs.forEach(([key, s]) => {
        const c = (this.colors[s.color] || this.colors.red).hex;
        const m = L.marker(this.kmToLatLng(Number(s.km)), { draggable: true, zIndexOffset: 500, icon: L.divIcon({ className: '', iconSize: null,
          html: '<div style="transform:translate(-50%,-50%);width:26px;height:26px;border-radius:7px;background:' + c + ';color:#fff;border:2.5px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.5);display:grid;place-items:center;cursor:grab">' + this.arrow(s.direction) + '</div>' }) })
          .bindTooltip(esc('KM ' + Number(s.km) + ' · ' + (this.dirs[s.direction]?.label || 'Arah belum diisi') + ' · ' + (this.colors[s.color]?.label || '')), { direction: 'top', offset: [0, -14] })
          .addTo(layer);
        drag(m, 'sign', key);
      });
      Object.entries(this.read('marshal')).filter(([, p]) => this.valid(p)).forEach(([key, mr]) => {
        const shift = signKm.has(Number(mr.km).toFixed(2)) ? 'translate(-50%,-100%) translate(0,-15px)' : 'translate(-50%,-100%)';
        const n = Number(mr.count) > 1 ? ' ×' + Number(mr.count) : '';
        const m = L.marker(this.kmToLatLng(Number(mr.km)), { draggable: true, zIndexOffset: 600, icon: L.divIcon({ className: '', iconSize: null,
          html: '<div style="transform:' + shift + ';display:flex;flex-direction:column;align-items:center;cursor:grab"><span style="padding:3px 8px;border-radius:999px;background:#141414;color:#cdee4b;font:800 11px sans-serif;white-space:nowrap;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)">🦺 ' + esc(mr.team || 'Marshal') + n + '</span><i style="width:2.5px;height:8px;background:#141414"></i></div>' }) })
          .bindTooltip(esc('KM ' + Number(mr.km) + ' · Marshal ' + (mr.team || '') + n), { direction: 'top', offset: [0, -30] })
          .addTo(layer);
        drag(m, 'marshal', key);
      });
    },

    openAdd(km) {
      this.modal = { mode: 'add', kind: this.last.kind, key: null, km, direction: this.last.direction, color: this.last.color,
        team: this.last.team, count: 1, note: '', error: '' };
    },
    openEdit(kind, key) {
      const p = this.read(kind)[key];
      if (! p) return;
      this.modal = { mode: 'edit', kind, key, km: Number(p.km), direction: p.direction || '', color: p.color || 'red',
        team: p.team || '', count: Number(p.count) || 1, note: p.note || '', error: '' };
    },
    close() { this.modal = null; this.showGhost(null); },

    async saveModal() {
      const m = this.modal, km = Number(m.km);
      if (m.km === '' || isNaN(km) || km < 0) return m.error = 'KM harus berupa angka.';
      if (km > this.epMax + 0.05) return m.error = 'KM melebihi panjang rute (' + this.epMax.toFixed(2) + ' km).';
      if (m.kind === 'marshal' && ! String(m.team).trim()) return m.error = 'Isi tim / asal marshal.';
      const cur = this.read(m.kind), key = m.key || uid();
      cur[key] = m.kind === 'sign'
        ? { km: Math.round(km * 100) / 100, direction: m.direction || null, color: m.color, note: m.note || '' }
        : { km: Math.round(km * 100) / 100, team: String(m.team).trim(), count: Math.max(1, Number(m.count) || 1), note: m.note || '' };
      Object.assign(this.last, m.kind === 'sign' ? { kind: 'sign', direction: m.direction || 'straight', color: m.color } : { kind: 'marshal', team: String(m.team).trim() });
      wire.$set(PATH[m.kind], cur, false);
      this.close();
      await this.persist();
    },
    async remove() {
      const m = this.modal;
      if (! confirm('Hapus ' + (m.kind === 'sign' ? 'sign' : 'marshal') + ' di KM ' + m.km + '?')) return;
      const cur = this.read(m.kind);
      delete cur[m.key];
      wire.$set(PATH[m.kind], cur, false);
      this.close();
      await this.persist();
    },
    async persist() {
      this.busy = true; this.status = 'Menyimpan…'; this.statusCls = '';
      try {
        const err = await wire.quickSave();
        const t = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        this.status = err ? '⚠ ' + err : '✓ Tersimpan ' + t;
        this.statusCls = err ? 'err' : 'ok';
      } catch (e) {
        this.status = '⚠ Gagal menyimpan — cek koneksi lalu coba lagi.'; this.statusCls = 'err';
      } finally {
        this.busy = false;
        this.draw();
      }
    },
  };
};
</script>
<div
  wire:ignore
  class="cpk"
  x-data="crewPicker({
    route: @js($route),
    epMax: @js($epMax),
    color: @js($record->color ?: '#E53935'),
    colors: @js(\App\Models\GtrCategory::SIGN_COLORS),
    dirs: @js(\App\Models\GtrCategory::SIGN_DIRECTIONS),
    teams: @js(\App\Models\GtrCategory::MARSHAL_TEAMS),
  })"
  x-on:keydown.escape.window="modal && close()"
  x-effect="showGhost(modal ? modal.km : null)"
  style="isolation:isolate"
>
  <div x-ref="map" style="height:560px;border-radius:12px;overflow:hidden;border:1px solid rgba(0,0,0,.1)"></div>

  <div class="cpk-bar">
    <span><b>Klik rute</b> = tambah titik</span>
    <span><b>Klik penanda</b> = edit / hapus</span>
    <span><b>Geser penanda</b> = pindah KM</span>
    <span class="cpk-st" :class="statusCls" x-text="status"></span>
  </div>

  <template x-teleport="body">
    <div class="cpk cpk-back" x-show="modal" x-cloak x-on:click.self="close()">
      <template x-if="modal">
        <form class="cpk-win" x-on:submit.prevent="saveModal()">
          <div class="cpk-hd">
            <h3 x-text="(modal.mode === 'add' ? 'Tambah titik' : (modal.kind === 'sign' ? 'Edit sign' : 'Edit marshal')) + ' · KM ' + modal.km"></h3>
            <button type="button" class="cpk-x" x-on:click="close()" aria-label="Tutup">✕</button>
          </div>
          <div class="cpk-bd">
            <div class="cpk-seg" x-show="modal.mode === 'add'">
              <button type="button" :class="modal.kind === 'sign' && 'on'" x-on:click="modal.kind = 'sign'">Sign arah</button>
              <button type="button" :class="modal.kind === 'marshal' && 'on'" x-on:click="modal.kind = 'marshal'">Marshal</button>
            </div>

            <div>
              <label class="cpk-lab">KM</label>
              <input class="cpk-in" type="number" step="0.01" min="0" x-model="modal.km" required>
              <div class="cpk-hint" style="margin-top:4px">Posisi ditandai lingkaran hijau di peta. Bisa juga menggeser penandanya.</div>
            </div>

            <template x-if="modal.kind === 'sign'">
              <div style="display:grid;gap:14px">
                <div>
                  <label class="cpk-lab">Arah (dari arah lari peserta)</label>
                  <div class="cpk-dirs">
                    <template x-for="(d, k) in dirs" :key="k">
                      <button type="button" class="cpk-dir" :class="modal.direction === k && 'on'" x-on:click="modal.direction = k"
                        :style="'--c:' + (colors[modal.color] || colors.red).hex">
                        <i x-html="arrow(k)"></i><span x-text="d.label"></span>
                      </button>
                    </template>
                  </div>
                </div>
                <div>
                  <label class="cpk-lab">Warna sign</label>
                  <div class="cpk-cols">
                    <template x-for="(c, k) in colors" :key="k">
                      <button type="button" class="cpk-col" :class="modal.color === k && 'on'" x-on:click="modal.color = k" :style="'--c:' + c.hex">
                        <span></span><b x-text="c.label"></b>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </template>

            <template x-if="modal.kind === 'marshal'">
              <div style="display:grid;gap:14px">
                <div>
                  <label class="cpk-lab">Tim / asal</label>
                  <div class="cpk-row" style="flex-wrap:wrap;margin-bottom:8px">
                    <template x-for="t in teams" :key="t">
                      <button type="button" class="cpk-chip" :class="modal.team === t && 'on'" x-on:click="modal.team = t" x-text="t"></button>
                    </template>
                  </div>
                  <input class="cpk-in" type="text" maxlength="60" x-model="modal.team" placeholder="atau ketik nama tim lain">
                </div>
                <div>
                  <label class="cpk-lab">Jumlah orang</label>
                  <div class="cpk-row">
                    <button type="button" class="cpk-step" x-on:click="modal.count = Math.max(1, Number(modal.count) - 1)">−</button>
                    <input class="cpk-in" type="number" min="1" max="50" x-model="modal.count" style="width:80px;text-align:center">
                    <button type="button" class="cpk-step" x-on:click="modal.count = Math.min(50, Number(modal.count) + 1)">+</button>
                  </div>
                </div>
              </div>
            </template>

            <div>
              <label class="cpk-lab">Keterangan (opsional)</label>
              <input class="cpk-in" type="text" maxlength="200" x-model="modal.note"
                :placeholder="modal.kind === 'sign' ? 'Masuk jalan setapak kiri masjid' : 'Jaga simpang, arahkan ke kanan'">
            </div>

            <div class="cpk-err" x-show="modal.error" x-text="modal.error"></div>
          </div>
          <div class="cpk-ft">
            <button type="button" class="cpk-btn del" x-show="modal.mode === 'edit'" x-on:click="remove()">Hapus</button>
            <span x-show="modal.mode !== 'edit'" style="margin-right:auto"></span>
            <button type="button" class="cpk-btn gh" x-on:click="close()">Batal</button>
            <button type="submit" class="cpk-btn pri" :disabled="busy">Simpan</button>
          </div>
        </form>
      </template>
    </div>
  </template>
</div>
@endif
