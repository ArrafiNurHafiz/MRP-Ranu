<?php

declare(strict_types=1);

use App\Domain\Access\Enums\Permission;

/**
 * Matriks default yang diizinkan per docs/04-hak-akses.md.
 * Disalin independen dari seeder agar test menangkap drift.
 * Divisi admin dites terpisah (bypass tanpa baris pivot).
 */
return [
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
