<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['code' => 'admin', 'name' => 'Admin'],
            ['code' => 'planning', 'name' => 'Perencanaan'],
            ['code' => 'purchasing', 'name' => 'Pembelian'],
            ['code' => 'production', 'name' => 'Produksi'],
            ['code' => 'warehouse', 'name' => 'Gudang'],
            ['code' => 'quality', 'name' => 'Kendali Mutu'],
            ['code' => 'sales', 'name' => 'Penjualan'],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['code' => $division['code']],
                ['name' => $division['name'], 'is_active' => true],
            );
        }
    }
}
