<?php

namespace Database\Seeders;

use App\Models\ContactChannel;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['type' => 'instagram', 'label' => 'Instagram', 'value' => '@runnersgerung', 'link' => 'https://www.instagram.com/runnersgerung/'],
            ['type' => 'whatsapp', 'label' => 'WhatsApp Group', 'value' => 'Chat Panitia', 'link' => 'https://wa.me/'],
            ['type' => 'email', 'label' => 'Email', 'value' => 'info@runnersgerung.id', 'link' => 'mailto:info@runnersgerung.id'],
            ['type' => 'maps', 'label' => 'Tikum di Maps', 'value' => 'Kantor Bupati Lobar', 'link' => 'https://www.google.com/maps/search/?api=1&query=-8.680761,116.136849'],
        ];
        foreach ($rows as $i => $r) {
            ContactChannel::updateOrCreate(
                ['type' => $r['type']],
                array_merge($r, ['sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
