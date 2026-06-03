<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site.name', 'value' => 'Runger', 'group' => 'general', 'type' => 'text', 'label' => 'Site Name'],
            ['key' => 'site.tagline', 'value' => 'Runners Gerung — Lari Bareng, Sehat Bareng', 'group' => 'general', 'type' => 'text', 'label' => 'Site Tagline'],
            ['key' => 'site.location', 'value' => 'Gerung, Lombok Barat, NTB', 'group' => 'general', 'type' => 'text', 'label' => 'Lokasi Komunitas'],
            ['key' => 'site.year_founded', 'value' => '2025', 'group' => 'general', 'type' => 'text', 'label' => 'Tahun Berdiri'],
            ['key' => 'social.instagram', 'value' => '@runnersgerung', 'group' => 'social', 'type' => 'text', 'label' => 'Instagram Handle'],
            ['key' => 'social.instagram_url', 'value' => 'https://www.instagram.com/runnersgerung/', 'group' => 'social', 'type' => 'url', 'label' => 'Instagram URL'],
            ['key' => 'social.strava_url', 'value' => '', 'group' => 'social', 'type' => 'url', 'label' => 'Strava Club URL'],
            ['key' => 'social.whatsapp_url', 'value' => 'https://wa.me/', 'group' => 'social', 'type' => 'url', 'label' => 'WhatsApp Group URL'],
            ['key' => 'tikum.default_lat', 'value' => '-8.680761', 'group' => 'general', 'type' => 'text', 'label' => 'Tikum Default Latitude'],
            ['key' => 'tikum.default_lng', 'value' => '116.136849', 'group' => 'general', 'type' => 'text', 'label' => 'Tikum Default Longitude'],
            ['key' => 'tikum.default_label', 'value' => 'Depan Kantor Bupati Lobar', 'group' => 'general', 'type' => 'text', 'label' => 'Tikum Default Label'],
            ['key' => 'hero.title', 'value' => 'Lari Bareng, Sehat Bareng di Gerung', 'group' => 'homepage', 'type' => 'text', 'label' => 'Homepage Hero Title'],
            ['key' => 'hero.subtitle', 'value' => 'Komunitas lari Lombok Barat. Setiap Jumat malam, kita kumpul di Kantor Bupati buat lari bareng.', 'group' => 'homepage', 'type' => 'textarea', 'label' => 'Homepage Hero Subtitle'],
        ];

        foreach ($settings as $i => $s) {
            Setting::updateOrCreate(['key' => $s['key']], array_merge($s, ['sort_order' => $i]));
        }
    }
}
