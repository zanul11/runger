<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-400">Jenis Email</label>
                <select wire:model.live="type"
                    class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800">
                    <option value="payment">Konfirmasi Pembayaran (Lunas)</option>
                    <option value="reminder">Pengingat Pembayaran (Belum Bayar)</option>
                    <option value="registration">Konfirmasi Pendaftaran</option>
                </select>
            </div>
            <div class="min-w-[18rem] flex-1">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-400">Data Peserta (opsional)</label>
                <select wire:model.live="registrationId"
                    class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800">
                    <option value="">— Contoh dummy —</option>
                    @foreach($this->registrationOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ $this->getPreviewUrl() }}" target="_blank" rel="noopener"
               class="fi-btn inline-flex h-10 items-center gap-1 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white">
                Buka di tab baru ↗
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm dark:border-white/10">
            <iframe
                src="{{ $this->getPreviewUrl() }}"
                wire:key="preview-{{ $type }}-{{ $registrationId ?? 'dummy' }}"
                style="width:100%;height:78vh;border:0;background:#fff"
                title="Preview Email"></iframe>
        </div>
    </div>
</x-filament-panels::page>
