{{--
  Peta pemilih posisi untuk admin kategori GTR: klik rute → KM terisi → tambah WS / CP / Info Jalur
  langsung ke repeater (data.water_stations / data.course_pois). Juga menampilkan peringatan data.
--}}
@php
  $route = $record?->route_points ?? [];
  $ep = $record?->elevation_profile ?? [];
  $epMax = count($ep) ? (float) end($ep)[0] : null;
  $poiTypes = \App\Models\GtrCategory::POI_TYPES;
@endphp

@if(empty($route))
  <div style="padding:14px 16px;border-radius:12px;background:rgba(245,158,11,.1);color:#b45309;font-size:13px">
    Upload file GPX lalu simpan dulu — setelah itu peta pemilih posisi WS / CP / Info Jalur akan muncul di sini.
  </div>
@else
<div
  wire:ignore
  x-data="{
    route: @js($route),
    epMax: @js($epMax),
    color: @js($record->color ?: '#E53935'),
    poiTypes: @js($poiTypes),
    map: null, layer: null, pick: null, cum: [], total: 0,
    sel: null, warnings: [],
    init() {
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
      this.cum = [0];
      for (let i = 1; i < this.route.length; i++) {
        const [a1, o1] = this.route[i - 1], [a2, o2] = this.route[i];
        const h = Math.sin(rad(a2 - a1) / 2) ** 2 + Math.cos(rad(a1)) * Math.cos(rad(a2)) * Math.sin(rad(o2 - o1) / 2) ** 2;
        this.cum[i] = this.cum[i - 1] + 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
      }
      this.total = this.cum[this.cum.length - 1] || 1;
      if (! this.epMax) this.epMax = this.total;

      this.map = L.map(this.$refs.map, { scrollWheelZoom: false });
      const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
      const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
      osm.addTo(this.map);
      L.control.layers({ 'Peta': osm, 'Satelit': sat }, null, { collapsed: false }).addTo(this.map);
      const line = L.polyline(this.route, { color: this.color, weight: 5 }).addTo(this.map);
      this.map.fitBounds(line.getBounds(), { padding: [20, 20] });
      this.layer = L.layerGroup().addTo(this.map);
      this.map.on('click', e => this.select(e.latlng));
      line.on('click', e => { L.DomEvent.stopPropagation(e); this.select(e.latlng); });

      this.draw();
      this.$wire.$watch('data.water_stations', () => this.draw());
      this.$wire.$watch('data.course_pois', () => this.draw());
    },
    // titik terdekat pada rute → KM (diskalakan ke panjang profil elevasi resmi)
    select(latlng) {
      const p = this.map.latLngToLayerPoint(latlng);
      let best = { d: Infinity, km: 0, ll: null };
      for (let i = 1; i < this.route.length; i++) {
        const a = this.map.latLngToLayerPoint(this.route[i - 1]), b = this.map.latLngToLayerPoint(this.route[i]);
        const dx = b.x - a.x, dy = b.y - a.y, len = dx * dx + dy * dy;
        const t = len ? Math.max(0, Math.min(1, ((p.x - a.x) * dx + (p.y - a.y) * dy) / len)) : 0;
        const x = a.x + t * dx, y = a.y + t * dy, d = Math.hypot(p.x - x, p.y - y);
        if (d < best.d) best = { d, km: this.cum[i - 1] + (this.cum[i] - this.cum[i - 1]) * t, ll: this.map.layerPointToLatLng(L.point(x, y)) };
      }
      const km = Math.round(best.km / this.total * this.epMax * 100) / 100;
      this.sel = { km, ll: best.ll, far: best.d > 40 };
      if (this.pick) this.map.removeLayer(this.pick);
      this.pick = L.circleMarker(best.ll, { radius: 8, color: '#fff', weight: 3, fillColor: '#111', fillOpacity: 1 }).addTo(this.map);
    },
    kmToLatLng(km) {
      const k = Math.max(0, Math.min(this.total, km / this.epMax * this.total));
      let j = 1; while (j < this.cum.length && this.cum[j] < k) j++;
      if (j >= this.cum.length) j = this.cum.length - 1;
      const seg = this.cum[j] - this.cum[j - 1], t = seg ? (k - this.cum[j - 1]) / seg : 0;
      const a = this.route[j - 1], b = this.route[j];
      return [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t];
    },
    items(path) {
      const v = this.$wire.$get(path) || {};
      return Array.isArray(v) ? v : Object.values(v);
    },
    draw() {
      if (! this.layer) return;
      this.layer.clearLayers();
      const badge = (txt, bg) => L.divIcon({ className: '', iconSize: null, html: '<div style=&quot;transform:translate(-50%,-50%);display:inline-block;padding:2px 7px;border-radius:999px;background:' + bg + ';color:#fff;font:800 11px sans-serif;white-space:nowrap;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)&quot;>' + txt + '</div>' });
      const pts = this.items('data.water_stations').filter(p => p && p.km !== '' && p.km != null && ! isNaN(p.km));
      const sorted = [...pts].sort((a, b) => a.km - b.km);
      let n = { ws: 0, cp: 0, cop: 0 };
      const ptColor = { ws: '#2EA6E6', cp: '#F97316', cop: '#DC2626' };
      sorted.forEach(p => {
        const type = ['cp', 'cop'].includes(p.type) ? p.type : 'ws';
        n[type]++;
        L.marker(this.kmToLatLng(Number(p.km)), { icon: badge(type.toUpperCase() + n[type] + ' · ' + Number(p.km), ptColor[type]) }).addTo(this.layer);
      });
      this.items('data.course_pois').filter(p => p && p.km !== '' && p.km != null && ! isNaN(p.km) && this.poiTypes[p.type]).forEach(p => {
        L.marker(this.kmToLatLng(Number(p.km)), { icon: badge(this.poiTypes[p.type].icon + ' ' + Number(p.km), '#374151') }).addTo(this.layer);
      });
      this.check(sorted);
    },
    check(sorted) {
      const w = [], max = this.epMax;
      sorted.forEach(p => { if (Number(p.km) > max + 0.05) w.push('KM ' + p.km + ' melebihi panjang rute (' + max.toFixed(1) + ' km).'); });
      const ws = sorted.filter(p => ! ['cp', 'cop'].includes(p.type)).map(p => Number(p.km));
      let prev = 0;
      [...ws, max].forEach(k => { if (k - prev > 5) w.push('Jarak tanpa water station ' + prev.toFixed(1) + ' → ' + k.toFixed(1) + ' km (' + (k - prev).toFixed(1) + ' km) — pertimbangkan tambah WS.'); prev = k; });
      let lastCut = null, lastKm = null;
      sorted.forEach(p => {
        const m = /^(\d{1,2})[:.](\d{2})$/.exec((p.cutoff || '').trim());
        if (! m) return;
        const c = Number(m[1]) * 60 + Number(m[2]);
        if (lastCut !== null && c <= lastCut) w.push('Cut-off KM ' + p.km + ' (' + p.cutoff + ') tidak lebih lambat dari pos sebelumnya di KM ' + lastKm + '.');
        lastCut = c; lastKm = p.km;
      });
      const seen = {};
      sorted.forEach(p => { const k = Number(p.km).toFixed(2); if (seen[k]) w.push('Ada dua pos di KM ' + Number(p.km) + '.'); seen[k] = 1; });
      this.warnings = w;
    },
    uid() {
      return (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
        : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); });
    },
    add(kind, type) {
      if (! this.sel) return;
      const path = kind === 'poi' ? 'data.course_pois' : 'data.water_stations';
      let cur = JSON.parse(JSON.stringify(this.$wire.$get(path) || {}));
      if (Array.isArray(cur)) cur = Object.assign({}, cur);
      cur[this.uid()] = kind === 'poi'
        ? { type, km: this.sel.km, km_end: null, title: '', note: '' }
        : { type, km: this.sel.km, name: '', cutoff: '', location: '', facilities: [], notes: '' };
      this.$wire.$set(path, cur);
    },
  }"
  style="isolation:isolate"
>
  <div x-ref="map" style="height:420px;border-radius:12px;overflow:hidden;border:1px solid rgba(0,0,0,.1)"></div>

  <div style="margin-top:12px;display:flex;flex-wrap:wrap;align-items:center;gap:8px">
    <template x-if="! sel">
      <span style="font-size:13px;opacity:.7">Klik titik mana pun di rute untuk mengambil posisi KM-nya.</span>
    </template>
    <template x-if="sel">
      <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
        <span style="font-weight:800;font-size:15px">KM <span x-text="sel.km.toFixed(2)"></span></span>
        <span x-show="sel.far" style="font-size:12px;color:#b45309">(diambil titik rute terdekat)</span>
        <x-filament::button size="sm" color="info" x-on:click="add('point', 'ws')">+ Water Station</x-filament::button>
        <x-filament::button size="sm" color="warning" x-on:click="add('point', 'cp')">+ Check Point</x-filament::button>
        <select x-ref="poiType" style="font-size:13px;padding:5px 30px 5px 8px;border-radius:8px;border:1px solid rgba(0,0,0,.2);background:transparent">
          @foreach($poiTypes as $key => $def)
            <option value="{{ $key }}">{{ $def['icon'] }} {{ $def['label'] }}</option>
          @endforeach
        </select>
        <x-filament::button size="sm" color="gray" x-on:click="add('poi', $refs.poiType.value)">+ Info Jalur</x-filament::button>
      </div>
    </template>
  </div>

  <template x-if="warnings.length">
    <ul style="margin-top:12px;padding:10px 14px;border-radius:10px;background:rgba(245,158,11,.1);color:#b45309;font-size:12.5px;list-style:disc inside">
      <template x-for="w in warnings"><li x-text="w"></li></template>
    </ul>
  </template>
</div>
@endif
