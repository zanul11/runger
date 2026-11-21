<?php

namespace App\Services;

/** Catatan perubahan import (dipakai untuk preview & hasil). */
class RosterImportLog
{
    private array $changes = [];

    private array $warnings = [];

    public function change(string $type, string $target, ?string $from = null, ?string $to = null): void
    {
        $this->changes[] = compact('type', 'target', 'from', 'to');
    }

    public function warn(string $msg): void
    {
        $this->warnings[] = $msg;
    }

    public function result(): array
    {
        $summary = array_fill_keys(['tambah', 'ubah', 'kosongkan', 'hapus'], 0);
        foreach ($this->changes as $c) {
            $summary[$c['type']] = ($summary[$c['type']] ?? 0) + 1;
        }

        return ['changes' => $this->changes, 'warnings' => $this->warnings, 'summary' => $summary];
    }
}
