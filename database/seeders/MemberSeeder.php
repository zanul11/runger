<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Founder Runger', 'role' => 'Founder · Penggagas', 'photo' => 'founder.jpeg', 'instagram' => '@runnersgerung', 'sort_order' => 1],
            ['name' => 'Pengurus 1', 'role' => 'Ketua', 'sort_order' => 2],
            ['name' => 'Pengurus 2', 'role' => 'Bendahara', 'sort_order' => 3],
            ['name' => 'Pengurus 3', 'role' => 'Sekretaris', 'sort_order' => 4],
            ['name' => 'Pengurus 4', 'role' => 'Koord. Lari', 'sort_order' => 5],
            ['name' => 'Pengurus 5', 'role' => 'Dokumentasi', 'sort_order' => 6],
        ];
        foreach ($rows as $r) {
            Member::updateOrCreate(['name' => $r['name']], array_merge($r, ['is_active' => true]));
        }
    }
}
