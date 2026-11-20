<?php

declare(strict_types=1);

namespace App\Domain\Access\Enums;

/**
 * Satu-satunya sumber key permission (docs/04-hak-akses.md).
 * Key baru wajib disertai test yang menolak divisi tanpa pemberian akses.
 */
enum Permission: string
{
    case MasterItemsView = 'master.items.view';
    case MasterItemsManage = 'master.items.manage';
    case MasterBomsView = 'master.boms.view';
    case MasterBomsManage = 'master.boms.manage';
    case MasterRoutingsView = 'master.routings.view';
    case MasterRoutingsManage = 'master.routings.manage';
    case MasterSuppliersManage = 'master.suppliers.manage';
    case MasterSubcontractorsManage = 'master.subcontractors.manage';
    case MasterCustomersManage = 'master.customers.manage';
    case MasterWarehousesManage = 'master.warehouses.manage';
    case AccessDivisionsManage = 'access.divisions.manage';
    case InventoryStockView = 'inventory.stock.view';
    case InventoryMovementsManage = 'inventory.movements.manage';
    case DemandSalesOrdersManage = 'demand.sales_orders.manage';
    case DemandForecastsManage = 'demand.forecasts.manage';
    case DemandMpsManage = 'demand.mps.manage';
    case MrpRunsManage = 'mrp.runs.manage';
    case PurchasingRequisitionsManage = 'purchasing.requisitions.manage';
    case PurchasingOrdersManage = 'purchasing.orders.manage';
    case PurchasingReceiptsManage = 'purchasing.receipts.manage';
    case ProductionWorkOrdersManage = 'production.work_orders.manage';
    case ProductionMaterialIssuesManage = 'production.material_issues.manage';
    case ProductionCompletionsManage = 'production.completions.manage';
    case SubcontractingOrdersManage = 'subcontracting.orders.manage';
    case QualityInspectionsManage = 'quality.inspections.manage';
    case QualityInspectionsOverride = 'quality.inspections.override';
    case ReportingReportsView = 'reporting.reports.view';

    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    public function label(): string
    {
        return match ($this) {
            self::MasterItemsView => 'Lihat item',
            self::MasterItemsManage => 'Ubah item',
            self::MasterBomsView => 'Lihat BOM',
            self::MasterBomsManage => 'Ubah BOM',
            self::MasterRoutingsView => 'Lihat routing',
            self::MasterRoutingsManage => 'Ubah routing',
            self::MasterSuppliersManage => 'Kelola supplier',
            self::MasterSubcontractorsManage => 'Kelola subcontractor',
            self::MasterCustomersManage => 'Kelola customer',
            self::MasterWarehousesManage => 'Kelola gudang',
            self::AccessDivisionsManage => 'Kelola divisi & matriks akses',
            self::InventoryStockView => 'Lihat stok',
            self::InventoryMovementsManage => 'Mutasi / adjustment / opname stok',
            self::DemandSalesOrdersManage => 'Kelola sales order',
            self::DemandForecastsManage => 'Kelola forecast',
            self::DemandMpsManage => 'Kelola MPS',
            self::MrpRunsManage => 'Jalankan MRP / firm order',
            self::PurchasingRequisitionsManage => 'Kelola purchase requisition',
            self::PurchasingOrdersManage => 'Kelola purchase order',
            self::PurchasingReceiptsManage => 'Posting terima pembelian',
            self::ProductionWorkOrdersManage => 'Kelola work order',
            self::ProductionMaterialIssuesManage => 'Issue material produksi',
            self::ProductionCompletionsManage => 'Terima hasil produksi',
            self::SubcontractingOrdersManage => 'Kelola SCO / dispatch / terima maklon',
            self::QualityInspectionsManage => 'Inspeksi / release / reject QC',
            self::QualityInspectionsOverride => 'Override hasil QC',
            self::ReportingReportsView => 'Lihat & ekspor laporan',
        };
    }
}
