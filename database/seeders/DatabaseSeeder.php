<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::query()->upsert([
            ['name' => 'hr_admin', 'description' => 'Mengelola master data karyawan'],
            ['name' => 'payroll_admin', 'description' => 'Membaca data untuk proses penggajian'],
            ['name' => 'viewer', 'description' => 'Akses baca terbatas'],
        ], ['name'], ['description']);
    }
}
