<?php

declare(strict_types=1);

use App\Domain\Access\Enums\Permission;
use App\Domain\Access\Models\Division;
use App\Domain\Access\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

dataset('division-permissions', function () {
    $matrix = require __DIR__.'/../Fixtures/access-matrix.php';

    foreach ($matrix as $code => $allowed) {
        foreach (Permission::cases() as $permission) {
            yield $code.':'.$permission->value => [
                $code,
                $permission->value,
                in_array($permission, $allowed, true),
            ];
        }
    }
});

it('seeds seven divisions and every permission key', function () {
    expect(Division::count())->toBe(7)
        ->and(PermissionModel::pluck('key')->sort()->values()->all())
        ->toEqualCanonicalizing(array_column(Permission::cases(), 'value'));
});

it('matches the default access matrix for every division-action pair', function (string $code, string $key, bool $allowed) {
    $user = userForDivision($code);

    expect(Gate::forUser($user)->allows($key))->toBe($allowed);
})->with('division-permissions');

it('bypasses gates for admin without pivot rows', function () {
    $admin = userForDivision('admin');

    expect($admin->division->permissions)->toHaveCount(0);

    foreach (Permission::cases() as $permission) {
        expect(Gate::forUser($admin)->allows($permission->value))->toBeTrue();
    }
});

it('limits the bypass to the admin division', function () {
    $planning = userForDivision('planning');
    $outsider = userForDivision('sales');

    // Kemampuan tak terdaftar pun hanya lolos untuk admin.
    expect(Gate::forUser(userForDivision('admin'))->allows('unknown.ability'))->toBeTrue()
        ->and(Gate::forUser($planning)->allows('unknown.ability'))->toBeFalse();

    // Divisi baru tanpa baris pivot menolak semua aksi (default deny).
    $fresh = Division::factory()->create();
    $freshUser = User::factory()->create(['division_id' => $fresh->id]);

    foreach (Permission::cases() as $permission) {
        expect(Gate::forUser($freshUser)->allows($permission->value))->toBeFalse();
    }
});

it('denies gates when the division is inactive', function () {
    $user = userForDivision('warehouse');
    $user->division->update(['is_active' => false]);

    expect(Gate::forUser($user->fresh())->allows(Permission::InventoryStockView->value))->toBeFalse();
});
