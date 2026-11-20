<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ranu.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'division_id' => Division::where('code', 'admin')->firstOrFail()->id,
            ],
        );
    }
}
