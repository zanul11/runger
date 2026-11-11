<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: tes HANYA boleh memakai sqlite :memory: (phpunit.xml).
     *
     * Bila config di-cache (`php artisan optimize` / `config:cache`), env dari phpunit.xml diabaikan
     * sehingga tes memakai MySQL sungguhan — dan RefreshDatabase menjalankan migrate:fresh di sana
     * (pernah menghapus seluruh DB lokal `runger`, 11 Nov 2026). Dicek SEBELUM trait apa pun jalan.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($this->app->configurationIsCached() || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Tes dihentikan demi keamanan data: koneksi DB [{$connection}: {$database}] bukan sqlite :memory:"
                .($this->app->configurationIsCached() ? ' karena config sedang di-cache' : '')
                .'. Jalankan `php artisan config:clear` lalu ulangi.'
            );
        }

        return parent::setUpTraits();
    }
}
