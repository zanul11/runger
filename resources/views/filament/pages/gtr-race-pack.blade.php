<x-filament-panels::page>
    @php
        $reg = $this->registration();
        $box = 'border:1px solid rgba(128,128,128,.22);border-radius:14px;padding:16px';
        $muted = 'color:rgba(128,128,128,1);font-size:12px';
    @endphp

    <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:start">
        {{-- Kiri: scan & hasil --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <div style="{{ $box }}">
                <div style="font-weight:700;margin-bottom:10px">Scan QR e-ticket / cari peserta</div>

                <div wire:ignore
                     x-data="{
                        on: false, scanner: null,
                        load(cb) {
                            if (window.Html5Qrcode) return cb();
                            const s = document.createElement('script');
                            s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                            s.onload = cb; document.head.appendChild(s);
                        },
                        toggle() {
                            if (this.on) { this.scanner.stop().then(() => { this.on = false; }); return; }
                            this.load(() => {
                                this.scanner = this.scanner || new Html5Qrcode('rp-reader');
                                let last = '', lastAt = 0;
                                this.scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 230 }, text => {
                                    if (text === last && Date.now() - lastAt < 4000) return;
                                    last = text; lastAt = Date.now();
                                    $wire.lookup(text);
                                }).then(() => { this.on = true; }).catch(e => alert('Kamera tidak bisa dibuka: ' + e));
                            });
                        }
                     }">
                    <div id="rp-reader" style="width:100%;max-width:360px;border-radius:12px;overflow:hidden"></div>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-qr-code" x-on:click="toggle()" style="margin-top:8px">
                        <span x-text="on ? 'Matikan kamera' : 'Scan dengan kamera'"></span>
                    </x-filament::button>
                </div>

                <form wire:submit="lookup" style="display:flex;gap:8px;margin-top:12px">
                    <x-filament::input.wrapper style="flex:1">
                        <x-filament::input type="text" wire:model="query" placeholder="No. registrasi (GTR2026…), BIB, atau nama" autofocus />
                    </x-filament::input.wrapper>
                    <x-filament::button type="submit">Cari</x-filament::button>
                </form>
            </div>

            @if(count($candidates))
                <div style="{{ $box }}">
                    <div style="font-weight:700;margin-bottom:8px">Pilih peserta</div>
                    @foreach($this->candidateRows() as $c)
                        <button type="button" wire:click="pick({{ $c->id }})" style="display:flex;justify-content:space-between;width:100%;padding:9px 10px;border-radius:10px;text-align:left;border:1px solid rgba(128,128,128,.18);margin-bottom:6px">
                            <span><b>{{ $c->full_name }}</b> <span style="{{ $muted }}">· {{ $c->category->distance ?? '' }}</span></span>
                            <span style="{{ $muted }}">{{ $c->bib_number ?: $c->nomor_registrasi }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @if($reg)
                @php
                    $paid = $reg->payment_status === 'paid';
                    $done = (bool) $reg->racepack_collected_at;
                    $bg = $done ? 'rgba(34,197,94,.1)' : ($paid ? 'rgba(59,130,246,.06)' : 'rgba(239,68,68,.08)');
                @endphp
                <div style="{{ $box }};background:{{ $bg }}">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
                        <div>
                            <div style="font-size:20px;font-weight:800">{{ $reg->full_name }}</div>
                            <div style="{{ $muted }}">{{ $reg->nomor_registrasi }} · {{ $reg->category->distance ?? '' }} {{ $reg->category->name ?? '' }}</div>
                        </div>
                        <div style="text-align:center;padding:8px 12px;border-radius:12px;background:#101010;color:#fff">
                            <div style="font-size:9px;letter-spacing:.14em;opacity:.7">BIB</div>
                            <div style="font-size:22px;font-weight:900">{{ $reg->bib_number ?: '—' }}</div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px">
                        <div><div style="{{ $muted }}">Jersey</div><div style="font-size:22px;font-weight:900">{{ $reg->size }}</div></div>
                        <div><div style="{{ $muted }}">Gender</div><div style="font-weight:700">{{ $reg->gender }}</div></div>
                        <div><div style="{{ $muted }}">Pembayaran</div><div style="font-weight:700;color:{{ $paid ? '#16a34a' : '#dc2626' }}">{{ ucfirst($reg->payment_status) }}</div></div>
                    </div>

                    @if($done)
                        <div style="margin-top:14px;font-weight:700;color:#16a34a">✓ Sudah diambil {{ $reg->racepack_collected_at->format('d M H:i') }}
                            @if($reg->racepack_picked_by) · diwakilkan {{ $reg->racepack_picked_by }} @endif
                            @if($reg->racepackCollector) · petugas {{ $reg->racepackCollector->name }} @endif
                        </div>
                        <x-filament::button size="sm" color="gray" wire:click="undo" wire:confirm="Batalkan status pengambilan race pack ini?" style="margin-top:10px">Batalkan</x-filament::button>
                    @elseif(! $paid)
                        <div style="margin-top:14px;font-weight:700;color:#dc2626">Pembayaran belum lunas — race pack tidak bisa diserahkan.</div>
                    @else
                        <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="pickedBy" placeholder="Diwakilkan oleh (opsional)" />
                            </x-filament::input.wrapper>
                            <x-filament::button color="success" size="lg" icon="heroicon-o-check-circle" wire:click="markCollected">Serahkan race pack</x-filament::button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Kanan: progres --}}
        <div style="display:flex;flex-direction:column;gap:16px" wire:poll.20s>
            <div style="{{ $box }}">
                <div style="font-weight:700;margin-bottom:10px">Progres pengambilan</div>
                @forelse($this->stats() as $s)
                    @php $pct = $s['total'] ? round($s['collected'] / $s['total'] * 100) : 0; @endphp
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;font-size:13px"><b>{{ $s['name'] }}</b><span>{{ $s['collected'] }} / {{ $s['total'] }} ({{ $pct }}%)</span></div>
                        <div style="height:8px;border-radius:4px;background:rgba(128,128,128,.18);margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ $pct }}%;background:#22c55e"></div></div>
                    </div>
                @empty
                    <div style="{{ $muted }}">Belum ada peserta lunas.</div>
                @endforelse
            </div>

            <div style="{{ $box }}">
                <div style="font-weight:700;margin-bottom:10px">Jersey yang belum diambil</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @forelse($this->sizesLeft() as $size => $n)
                        <div style="padding:8px 12px;border-radius:10px;border:1px solid rgba(128,128,128,.22);text-align:center;min-width:56px">
                            <div style="font-weight:900">{{ $size }}</div><div style="{{ $muted }}">{{ $n }}</div>
                        </div>
                    @empty
                        <div style="{{ $muted }}">Semua sudah diambil 🎉</div>
                    @endforelse
                </div>
            </div>

            <div style="{{ $box }}">
                <div style="font-weight:700;margin-bottom:10px">Terakhir diserahkan</div>
                @forelse($this->recent() as $r)
                    <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid rgba(128,128,128,.12);font-size:13px">
                        <span><b>{{ $r->bib_number ?: '—' }}</b> {{ $r->full_name }}</span>
                        <span style="{{ $muted }}">{{ $r->racepack_collected_at->format('d M H:i') }}</span>
                    </div>
                @empty
                    <div style="{{ $muted }}">Belum ada.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
