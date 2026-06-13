<?php

namespace Database\Seeders;

use App\Models\Software;
use Illuminate\Database\Seeder;

class SoftwareSeeder extends Seeder
{
    public function run(): void
    {
        $softwares = [
            [
                'name' => 'Visual Studio Code',
                'developer' => 'Microsoft',
                'version' => '1.102',
                'description' => 'Code editor ringan dan populer untuk pengembangan web dan aplikasi.',
                'estimated_minutes' => 20,
            ],
            [
                'name' => 'Google Chrome',
                'developer' => 'Google',
                'version' => 'Latest',
                'description' => 'Browser modern untuk pengujian website dan akses aplikasi web.',
                'estimated_minutes' => 15,
            ],
            [
                'name' => 'XAMPP',
                'developer' => 'Apache Friends',
                'version' => '8.2',
                'description' => 'Paket server lokal untuk menjalankan PHP, MySQL, dan Apache.',
                'estimated_minutes' => 25,
            ],
            [
                'name' => 'Laragon',
                'developer' => 'Laragon',
                'version' => '6.0',
                'description' => 'Lingkungan development lokal yang ringan dan cepat untuk Laravel.',
                'estimated_minutes' => 20,
            ],
            [
                'name' => 'Microsoft Word',
                'developer' => 'Microsoft',
                'version' => '2021',
                'description' => 'Aplikasi pengolah kata untuk dokumen, laporan, dan surat.',
                'estimated_minutes' => 30,
            ],
            [
                'name' => 'Adobe Acrobat Reader',
                'developer' => 'Adobe',
                'version' => 'Latest',
                'description' => 'Pembaca file PDF untuk melihat, mencetak, dan memberi anotasi.',
                'estimated_minutes' => 15,
            ],
            [
                'name' => 'WinRAR',
                'developer' => 'RARLab',
                'version' => '6.24',
                'description' => 'Aplikasi kompresi dan ekstraksi file arsip.',
                'estimated_minutes' => 10,
            ],
            [
                'name' => 'Git',
                'developer' => 'Git SCM',
                'version' => '2.4x',
                'description' => 'Version control system untuk manajemen source code.',
                'estimated_minutes' => 20,
            ],
        ];

        foreach ($softwares as $software) {
            Software::create($software);
        }
    }
}
