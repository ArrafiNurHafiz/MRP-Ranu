<?php

declare(strict_types=1);

use App\Domain\Access\Enums\Permission;
use App\Livewire\Access\AccessMatrix;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

it('opens the access matrix for admin', function () {
    $this->actingAs(userForDivision('admin'))
        ->get(route('access.matrix'))
        ->assertOk();
});

it('rejects every non-admin division', function (string $code) {
    $this->actingAs(userForDivision($code))
        ->get(route('access.matrix'))
        ->assertForbidden();
})->with(['planning', 'purchasing', 'production', 'warehouse', 'quality', 'sales']);

it('blocks non-admins at the save boundary', function () {
    $this->actingAs(userForDivision('planning'));

    // Montase Livewire menangkap exception; uji aksi mutasi langsung.
    $component = new AccessMatrix;

    expect(fn () => $component->save())->toThrow(AuthorizationException::class);
});

it('saves one division without touching the others and applies immediately', function () {
    $admin = userForDivision('admin');
    $purchasingUser = userForDivision('purchasing');
    $planningBefore = userForDivision('planning')->division->permissions->pluck('key')->all();

    $this->actingAs($admin);

    $newKeys = [Permission::MasterItemsView->value, Permission::PurchasingOrdersManage->value];

    Livewire::test(AccessMatrix::class)
        ->set('divisionId', $purchasingUser->division_id)
        ->set('selected', $newKeys)
        ->call('save')
        ->assertHasNoErrors();

    expect($purchasingUser->division->fresh()->permissions->pluck('key')->all())
        ->toEqualCanonicalizing($newKeys);

    // Divisi lain tidak tersentuh.
    expect(userForDivision('planning')->division->permissions->pluck('key')->all())
        ->toEqualCanonicalizing($planningBefore);

    // Perubahan langsung berlaku tanpa deploy.
    $freshPurchasing = $purchasingUser->fresh();
    expect(Gate::forUser($freshPurchasing)->allows(Permission::MasterItemsView->value))->toBeTrue()
        ->and(Gate::forUser($freshPurchasing)->allows(Permission::PurchasingRequisitionsManage->value))->toBeFalse();
});

it('ignores unknown keys submitted to the matrix', function () {
    $admin = userForDivision('admin');
    $sales = userForDivision('sales');

    $this->actingAs($admin);

    Livewire::test(AccessMatrix::class)
        ->set('divisionId', $sales->division_id)
        ->set('selected', [Permission::InventoryStockView->value, 'evil.key.inject'])
        ->call('save')
        ->assertHasNoErrors();

    expect($sales->division->fresh()->permissions->pluck('key')->all())
        ->toEqualCanonicalizing([Permission::InventoryStockView->value]);
});
