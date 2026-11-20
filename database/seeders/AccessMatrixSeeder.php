<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Access\Enums\Permission;
use App\Domain\Access\Models\Division;
use App\Domain\Access\Models\Permission as PermissionModel;
use Illuminate\Database\Seeder;

/**
 * Matriks default docs/04-hak-akses.md. Divisi admin tidak diberi baris:
 * aksesnya lewat Gate::before, bukan matriks.
 */
class AccessMatrixSeeder extends Seeder
{
    private const MATRIX = [
        'planning' => [
            Permission::MasterItemsView,
            Permission::MasterBomsView,
            Permission::MasterRoutingsView,
            Permission::MasterItemsManage,
            Permission::MasterBomsManage,
            Permission::InventoryStockView,
            Permission::DemandMpsManage,
            Permission::MrpRunsManage,
            Permission::ReportingReportsView,
        ],
        'purchasing' => [
            Permission::MasterItemsView,
            Permission::MasterSuppliersManage,
            Permission::MasterSubcontractorsManage,
            Permission::InventoryStockView,
            Permission::PurchasingRequisitionsManage,
            Permission::PurchasingOrdersManage,
            Permission::ReportingReportsView,
        ],
        'production' => [
            Permission::MasterItemsView,
            Permission::MasterBomsView,
            Permission::MasterRoutingsView,
            Permission::MasterRoutingsManage,
            Permission::InventoryStockView,
            Permission::ProductionWorkOrdersManage,
            Permission::ProductionMaterialIssuesManage,
            Permission::ProductionCompletionsManage,
            Permission::ReportingReportsView,
        ],
        'warehouse' => [
            Permission::MasterItemsView,
            Permission::InventoryStockView,
            Permission::InventoryMovementsManage,
            Permission::PurchasingReceiptsManage,
            Permission::ProductionMaterialIssuesManage,
            Permission::ProductionCompletionsManage,
            Permission::SubcontractingOrdersManage,
            Permission::ReportingReportsView,
        ],
        'quality' => [
            Permission::InventoryStockView,
            Permission::QualityInspectionsManage,
            Permission::ReportingReportsView,
        ],
        'sales' => [
            Permission::MasterItemsView,
            Permission::MasterCustomersManage,
            Permission::InventoryStockView,
            Permission::DemandSalesOrdersManage,
            Permission::DemandForecastsManage,
            Permission::ReportingReportsView,
        ],
    ];

    public function run(): void
    {
        foreach (self::MATRIX as $code => $permissions) {
            $division = Division::where('code', $code)->firstOrFail();
            $ids = PermissionModel::whereIn('key', array_map(fn (Permission $p) => $p->value, $permissions))
                ->pluck('id');
            $division->permissions()->sync($ids);
        }
    }
}
