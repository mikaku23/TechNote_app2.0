<?php

namespace Database\Seeders;

use App\Models\role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        role::create([
            'name' => 'Admin',
            'description' => 'Administrator with full system access',
            'is_active' => true,
        ]);

        role::create([
            'name' => 'Mahasiswa',
            'description' => 'Student account for service requests',
            'is_active' => true,
        ]);

        role::create([
            'name' => 'Dosen',
            'description' => 'Lecturer/Faculty account',
            'is_active' => true,
        ]);
    }
}
