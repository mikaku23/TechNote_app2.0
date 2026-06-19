<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get role IDs
        $adminRole = role::where('name', 'Admin')->firstOrFail();
        $mahasiswaRole = role::where('name', 'Mahasiswa')->firstOrFail();
        $dosenRole = role::where('name', 'Dosen')->firstOrFail();

        // Copy avatar image from assets to storage
        $sourceImagePath = public_path('assets/images/profile.png');
        $avatarFileName = 'avatars/users/profile.png';

        if (File::exists($sourceImagePath)) {
            Storage::disk('public')->put($avatarFileName, File::get($sourceImagePath));
        }

        // Create Admin User
        User::create([
            'role_id' => $adminRole->id,
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@technoteapp.local',
            'nim' => null,
            'nip' => '001',
            'no_hp' => '081234567890',
            'password' => Hash::make('123'),
            'security_question' => 'What is your favorite color?',
            'security_answer' => 'blue',
            'qr_code' => null,
            'qr_url' => null,
            'avatar' => $avatarFileName,
        ]);

        // Create Mahasiswa User
        User::create([
            'role_id' => $mahasiswaRole->id,
            'name' => 'Mahasiswa Test',
            'username' => 'mahasiswa',
            'email' => 'haliqksp@gmail.com',
            'nim' => '11111',
            'nip' => null,
            'no_hp' => '082285926175',
            'password' => Hash::make('123'),
            'security_question' => 'What is your pet name?',
            'security_answer' => 'fluffy',
            'qr_code' => null,
            'qr_url' => null,
            'avatar' => $avatarFileName,
        ]);

        // Create Dosen User
        User::create([
            'role_id' => $dosenRole->id,
            'name' => 'Dosen Test',
            'username' => 'dosen',
            'email' => 'adif63576@gmail.com',
            'nim' => null,
            'nip' => '22222',
            'no_hp' => '082285926175',
            'password' => Hash::make('123'),
            'security_question' => 'What is your birth place?',
            'security_answer' => 'jakarta',
            'qr_code' => null,
            'qr_url' => null,
            'avatar' => $avatarFileName,
        ]);
    }
}
