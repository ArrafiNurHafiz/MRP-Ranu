<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Enums\Permission;
use App\Domain\Access\Models\Permission as PermissionModel;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::updateOrCreate(
                ['key' => $permission->value],
                ['module' => $permission->module(), 'description' => $permission->label()],
            );
        }
    }
}
