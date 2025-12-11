<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// HR Module
use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Repositories\HR\EmployeeRepository;
use App\Interfaces\HR\LeaveRequestRepositoryInterface;
use App\Repositories\HR\LeaveRequestRepository;
use App\Interfaces\HR\ServiceRecordRepositoryInterface;
use App\Repositories\HR\ServiceRecordRepository;
use App\Interfaces\HR\TrainingRepositoryInterface;
use App\Repositories\HR\TrainingRepository;

// Procurement Module
use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Repositories\Procurement\SupplierRepository;
use App\Interfaces\Procurement\PurchaseRequestRepositoryInterface;
use App\Repositories\Procurement\PurchaseRequestRepository;
use App\Interfaces\Procurement\QuotationRepositoryInterface;
use App\Repositories\Procurement\QuotationRepository;
use App\Interfaces\Procurement\PurchaseOrderRepositoryInterface;
use App\Repositories\Procurement\PurchaseOrderRepository;
use App\Interfaces\Procurement\DeliveryRepositoryInterface;
use App\Repositories\Procurement\DeliveryRepository;

// Inventory Module
use App\Interfaces\Inventory\InventoryItemRepositoryInterface;
use App\Repositories\Inventory\InventoryItemRepository;
use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Repositories\Inventory\StockCardRepository;
use App\Interfaces\Inventory\InventoryAdjustmentRepositoryInterface;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use App\Interfaces\Inventory\PhysicalCountRepositoryInterface;
use App\Repositories\Inventory\PhysicalCountRepository;

// Financial Module
use App\Interfaces\Financial\CashAdvanceRepositoryInterface;
use App\Repositories\Financial\CashAdvanceRepository;
use App\Interfaces\Financial\DisbursementRepositoryInterface;
use App\Repositories\Financial\DisbursementRepository;
use App\Interfaces\Financial\LiquidationRepositoryInterface;
use App\Repositories\Financial\LiquidationRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // HR Module Repository Bindings
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->bind(ServiceRecordRepositoryInterface::class, ServiceRecordRepository::class);
        $this->app->bind(TrainingRepositoryInterface::class, TrainingRepository::class);

        // Procurement Module Repository Bindings
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(PurchaseRequestRepositoryInterface::class, PurchaseRequestRepository::class);
        $this->app->bind(QuotationRepositoryInterface::class, QuotationRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(DeliveryRepositoryInterface::class, DeliveryRepository::class);

        // Inventory Module Repository Bindings
        $this->app->bind(InventoryItemRepositoryInterface::class, InventoryItemRepository::class);
        $this->app->bind(StockCardRepositoryInterface::class, StockCardRepository::class);
        $this->app->bind(InventoryAdjustmentRepositoryInterface::class, InventoryAdjustmentRepository::class);
        $this->app->bind(PhysicalCountRepositoryInterface::class, PhysicalCountRepository::class);

        // Financial Module Repository Bindings
        $this->app->bind(CashAdvanceRepositoryInterface::class, CashAdvanceRepository::class);
        $this->app->bind(DisbursementRepositoryInterface::class, DisbursementRepository::class);
        $this->app->bind(LiquidationRepositoryInterface::class, LiquidationRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
