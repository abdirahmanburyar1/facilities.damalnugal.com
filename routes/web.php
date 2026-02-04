<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpiredController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\DispenceController;
use App\Http\Controllers\MohDispenseController;
use App\Http\Controllers\BackOrderController;
use App\Http\Controllers\MonthlyInventoryReportController;
use App\Http\Controllers\FacilityInventoryMovementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\FacilityReorderLevelController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Broadcast routes
Broadcast::routes(['middleware' => ['web', 'auth']]);

// Two-Factor Authentication Routes - These must be accessible without 2FA
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
});

// Welcome route - accessible to everyone
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('welcome');

// All routes that require 2FA
Route::middleware(['auth', 'verified', \App\Http\Middleware\TwoFactorAuth::class])->group(function () {
    // Dashboard routes
    Route::controller(App\Http\Controllers\DashboardController::class)
    ->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::post('/dashboard/facility/tracert-items', 'facilityTracertItems')->name('dashboard.facility.tracert-items');
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User Management Routes
    Route::prefix('users')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        // Create and Edit routes for navigation purposes
        Route::get('/create', function() {
            return Inertia::render('User/Create');
        })->name('users.create');
        Route::post('/store', [UserController::class, 'store'])->name('users.store');
        
        // User roles management
        Route::get('/{user}/roles', [UserController::class, 'showRoles'])->name('users.roles');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    
    // Role Management Routes
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles-permissions', [RoleController::class, 'getAllRoles'])->name('roles.get-all');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/users/{user}/roles', [RoleController::class, 'assignRoles'])->name('users.roles.assign');
    

    
    // Expired Management Routes
    Route::prefix('expired')->group(function () {
        Route::get('/', [ExpiredController::class, 'index'])->name('expired.index');
        Route::get('/create', [ExpiredController::class, 'create'])->name('expired.create');
        Route::post('/', [ExpiredController::class, 'store'])->name('expired.store');
        Route::get('/{expired}/edit', [ExpiredController::class, 'edit'])->name('expired.edit');
        Route::put('/{expired}', [ExpiredController::class, 'update'])->name('expired.update');
        Route::delete('/{expired}', [ExpiredController::class, 'destroy'])->name('expired.destroy');
        Route::get('/{transfer}/transfer', [ExpiredController::class, 'transfer'])->name('expired.transfer');
        Route::post('/dispose', [ExpiredController::class, 'dispose'])->name('expired.dispose');
    });
   
    // Inventory Routes
    Route::controller(InventoryController::class)
        ->prefix('/inventories')
        ->group(function () {
            Route::get('/', 'index')->name('inventories.index');
            Route::post('/store', 'store')->name('inventories.store');
            Route::put('/{inventory}', 'update')->name('inventories.update');
            Route::delete('/{inventory}', 'destroy')->name('inventories.destroy');
            Route::post('/bulk', 'bulk')->name('inventories.bulk');

            Route::post('/import', 'import')->name('inventories.import');
            Route::get('/get-locations', 'getLocations')->name('invetnories.getLocations');
            Route::get('/template-items', 'templateItems')->name('inventories.templateItems');
            // products for templates (used by facility reorder levels modal)
            Route::get('/template-products', [ReportController::class, 'getTemplateProducts'])->name('inventory.template-products');
        });
    
    // Settings Routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    // Facility Reorder Levels under inventories prefix
    Route::prefix('/inventories')
        ->name('inventories.')
        ->controller(FacilityReorderLevelController::class)
        ->group(function () {
        Route::get('/reorder-levels', 'index')->name('facility-reorder-levels.index');
        Route::post('/reorder-levels', 'store')->name('facility-reorder-levels.store');
        Route::post('/reorder-levels/import', 'import')->name('facility-reorder-levels.import');
        Route::put('/reorder-levels/{reorderLevel}', 'update')->name('facility-reorder-levels.update');
        Route::delete('/reorder-levels/{reorderLevel}', 'destroy')->name('facility-reorder-levels.destroy');
        Route::get('/reorder-levels/template/download', 'template')->name('facility-reorder-levels.template');
    });
    
    Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::controller(FacilityController::class)
        ->prefix('/facilities')
        ->group(function () {
            Route::get('/', 'index')->name('facilities.index');
            Route::post('/store', 'store')->name('facilities.store');
            Route::delete('/{facility}', 'destroy')->name('facilities.destroy');
        });

        // All Back Order routes consolidated in BackOrderController
        Route::controller(BackOrderController::class)->prefix('backorders')->group(function () {
            Route::get('/', 'index')->name('backorders.index');
            Route::get('/manage', 'manageBackOrder')->name('backorders.manage');
            Route::get('/history', 'showBackOrder')->name('backorders.history');
            Route::get('/{backOrderId}/histories', 'getBackOrderHistories')->name('backorders.histories');
            
            // Get back order items for orders and transfers
            Route::get('/order/{id}/get-back-order', 'getBackOrder')->name('backorders.get-back-order.order');
            Route::get('/transfer/{id}/get-back-order', 'getBackOrder')->name('backorders.get-back-order.transfer');
            
            // Back order actions
            Route::post('/receive', 'receiveBackOrder')->name('backorders.receive');
            Route::post('/received', 'received')->name('backorders.received');
            Route::post('/liquidate', 'liquidate')->name('backorders.liquidate');
            Route::post('/dispose', 'dispose')->name('backorders.dispose');
            
            // Back order attachments
            Route::post('/{backOrderId}/attachments', 'uploadBackOrderAttachment')->name('backorders.uploadAttachment');
            Route::delete('/{backOrderId}/attachments', 'deleteBackOrderAttachment')->name('backorders.deleteAttachment');
            
            // Test route
            Route::get('/test', 'testBackOrderRoute')->name('backorders.test');
        });

        // Order Management Routes
        Route::controller(OrderController::class)->prefix('orders')->group(function () {
            Route::get('/', 'index')->name('orders.index');
            Route::get('/{id}/show', 'show')->name('orders.show');
            Route::post('/change-status', 'changeItemStatus')->name('orders.change-status');
            Route::post('/reject', 'rejectOrder');

            // restore order
            Route::post('/restore-order', 'restoreOrder')->name('orders.restore-order');

            Route::get('/create', 'create')->name('orders.create');
            Route::post('/store', 'store')->name('orders.store');
            Route::get('/{order}/edit', 'edit')->name('orders.edit');
            Route::put('/{order}', 'update')->name('orders.update');
            Route::delete('/{order}', 'destroy')->name('orders.destroy');
            
            // dispatch info
            Route::post('/dispatch-info', 'dispatchInfo')->name('orders.dispatch-info');
            
            // update quantity
            Route::post('/update-quantity', 'updateQuantity')->name('orders.update-quantity');
            
            // Inventory check
            Route::post('/check/inventory', 'checkInventory')->name('orders.check-inventory');

            // Back order
            Route::post('/backorder', 'backorder')->name('orders.backorder');
            Route::post('/remove-back-order', 'removeBackOrder')->name('orders.remove-back-order');
            Route::post('/receive-back-order', 'receiveBackOrder')->name('orders.receive-back-order');

            // receivedQuantity
            Route::post('/update-received-quantity', 'receivedQuantity')->name('orders.receivedQuantity');
            
            // mark as delivered with form
            Route::post('/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('orders.mark-delivered');
            
            // delivery routes
            Route::prefix('delivery')->group(function () {
                Route::post('/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('delivery.mark-delivered');
                Route::get('/{orderId}/info', [DeliveryController::class, 'getDeliveryInfo'])->name('delivery.info');
            });
        });

        Route::controller(DispenceController::class)
        ->prefix('/dispence')
        ->group(function () {
            Route::get('/', 'index')->name('dispence.index');
            Route::get('/create', 'create')->name('dispence.create');
            Route::post('/store', 'store')->name('dispence.store');
            Route::get('/{id}/show', 'show')->name('dispence.show');

            // dispence.check-invnetory
            Route::post('/check-invnetory', 'checkInventory')->name('dispence.check-invnetory');
        });

        // MOH Dispense Routes
        Route::controller(MohDispenseController::class)
            ->prefix('/moh-dispense')
            ->group(function () {
                Route::get('/', 'index')->name('moh-dispense.index');
                Route::get('/create', 'create')->name('moh-dispense.create');
                Route::post('/store', 'store')->name('moh-dispense.store');
                Route::get('/{id}/show', 'show')->name('moh-dispense.show');
                Route::post('/{id}/validate-inventory', 'validateInventory')->name('moh-dispense.validate-inventory');
                Route::post('/{id}/process', 'process')->name('moh-dispense.process');
                Route::get('/download-template', 'downloadTemplate')->name('moh-dispense.download-template');
                Route::get('/test', function() {
                    try {
                        // Test if models can be instantiated
                        $mohDispense = new \App\Models\MohDispense();
                        $mohDispenseItem = new \App\Models\MohDispenseItem();
                        $inventoryService = new \App\Services\MohDispenseInventoryService();
                        
                        return response()->json([
                            'message' => 'MOH Dispense routes working',
                            'models' => 'Models can be instantiated',
                            'service' => 'MohDispenseInventoryService can be instantiated',
                            'user' => auth()->user() ? 'User authenticated' : 'No user',
                            'facility_id' => auth()->user() ? auth()->user()->facility_id : 'No facility',
                            'routes' => [
                                'validate' => route('moh-dispense.validate-inventory', 1),
                                'process' => route('moh-dispense.process', 1)
                            ]
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'message' => 'Error in test route',
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ], 500);
                    }
                })->name('moh-dispense.test');
            });

            // Transfer Management Routes

 Route::prefix('transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('/{id}/show', [TransferController::class, 'show'])->name('transfers.show');
        Route::get('/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('/store', [TransferController::class, 'store'])->name('transfers.store');
        Route::get('/{transfer}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
        Route::put('/{transfer}', [TransferController::class, 'update'])->name('transfers.update');
        Route::delete('/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');

        // get inventory for transfer
        Route::post('/inventory', [TransferController::class, 'getSourceInventoryDetail'])->name('transfers.inventory');
                
        // Route to get available inventories for transfer
        Route::get('/get-inventories', [TransferController::class, 'getInventories'])->name('transfers.getInventories');
               
         
        // Back order functionality
        Route::post('/backorder', [TransferController::class, 'backorder'])->name('transfers.backorder');
        Route::post('/remove-back-order', [TransferController::class, 'removeBackOrder'])->name('transfers.remove-back-order');
        
        // Item status change
        Route::post('/change-item-status', [TransferController::class, 'changeStatus'])->name('transfers.changeItemStatus');
        
        // receive transfer
        Route::post('/receive', [TransferController::class, 'receiveTransfer'])->name('transfers.receiveTransfer');
        
        // receive back order
        Route::post('/receive-back-order', [TransferController::class, 'receiveBackOrder'])->name('transfers.receiveBackOrder');
        
        // delete transfer item
        Route::get('/items/{id}', [TransferController::class, 'destroyItem'])->name('transfers.items.destroy');

        // update transfer item quantity
        Route::post('/update-item', [TransferController::class, 'updateItem'])->name('transfers.update-item');

        // transfer back order
        Route::get('/back-order', [TransferController::class, 'transferBackOrder'])->name('transfers.back-order');

        // transfer liquidate
        Route::post('/liquidate', [TransferController::class, 'transferLiquidate'])->name('transfers.liquidate');

        // transfer dispose
        Route::post('/dispose', [TransferController::class, 'transferDispose'])->name('transfers.dispose');


         // transfer update-quantity
         Route::post('/update-quantity', [TransferController::class, 'updateQuantity'])->name('transfers.update-quantity');

         // save transfer back orders
         Route::post('/save-back-orders', [TransferController::class, 'saveBackOrders'])->name('transfers.save-back-orders');

        // transfers.received-quantity
        Route::post('/received-quantity', [TransferController::class, 'receivedQuantity'])->name('transfers.received-quantity');
         
         // delete transfer back order
         Route::post('/delete-back-order', [TransferController::class, 'deleteBackOrder'])->name('transfers.delete-back-order');
          // change transfer status
          Route::post('/change-status', [TransferController::class, 'changeStatus'])->name('transfers.change-status');

           // receivedAllocationQuantity
           Route::post('/update-allocation-received-quantity', [TransferController::class, 'receivedAllocationQuantity'])->name('transfers.receivedAllocationQuantity');

        Route::post('/dispatch-info', [TransferController::class, 'dispatchInfo'])->name('transfers.dispatch-info');

        // mark transfer as delivered
        Route::post('/mark-delivered', [TransferController::class, 'markDelivered'])->name('transfers.mark-delivered');

    });
    // Report Routes
    Route::controller(ReportController::class)
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            // Reports Dashboard
            Route::get('/', 'index')->name('index');
            
            // Monthly Inventory Report Interface
            Route::get('/monthly-inventory', 'monthlyInventory')->name('monthly-inventory');
            
            // Generate Monthly Inventory Report
            Route::post('/monthly-inventory/generate', 'generateMonthlyReport')->name('monthly-inventory.generate');
            
            // View Monthly Inventory Report
            Route::get('/monthly-inventory/view', 'viewMonthlyReport')->name('monthly-inventory.view');
            
            // Check Report Status
            Route::get('/monthly-inventory/status', 'getReportStatus')->name('monthly-inventory.status');
            
            // Update Report Item
            Route::post('/monthly-inventory/update-item', 'updateReportItem')->name('monthly-inventory.update-item');
            
            // Save Report
            Route::post('/monthly-inventory/save', 'saveReport')->name('monthly-inventory.save');
            
            // Export Reports
            Route::get('/monthly-inventory/export/excel', 'exportMonthlyReportExcel')->name('monthly-inventory.export.excel');
            Route::get('/monthly-inventory/export/pdf', 'exportMonthlyReportPdf')->name('monthly-inventory.export.pdf');
            
            // Report Workflow Routes
            Route::post('/monthly-inventory/submit', [ReportController::class, 'submitMonthlyReport'])->name('monthly-inventory.submit');
            Route::post('/monthly-inventory/start-review', [ReportController::class, 'startMonthlyReportReview'])->name('monthly-inventory.start-review');
            Route::post('/monthly-inventory/approve', [ReportController::class, 'approveMonthlyReport'])->name('monthly-inventory.approve');
            Route::post('/monthly-inventory/reject', [ReportController::class, 'rejectMonthlyReport'])->name('monthly-inventory.reject');
            Route::post('/monthly-inventory/return-to-draft', [ReportController::class, 'returnMonthlyReportToDraft'])->name('monthly-inventory.return-to-draft');
            Route::post('/monthly-inventory/reopen', [ReportController::class, 'reopenMonthlyReport'])->name('monthly-inventory.reopen');
            
            // Inventory Movements (moved from FacilityInventoryMovementController)
            Route::get('/inventory-movements', 'inventoryMovements')->name('inventory-movements');
            Route::get('/inventory-movements/summary', 'inventoryMovementsSummary')->name('inventory-movements.summary');
            Route::get('/inventory-movements/export', 'exportInventoryMovements')->name('inventory-movements.export');
            
            // Transfers Report
            Route::get('/transfers', 'transfers')->name('transfers');
            Route::get('/transfers/summary', 'transfersSummary')->name('transfers.summary');
            Route::get('/transfers/export', 'exportTransfers')->name('transfers.export');

            // Order Reports
            Route::get('/orders', 'orders')->name('orders');
            Route::get('/orders/summary', 'ordersSummary')->name('orders.summary');
            Route::get('/orders/export', 'exportOrders')->name('orders.export');
        });

    // Monthly Inventory Report Routes
    Route::controller(MonthlyInventoryReportController::class)
        ->prefix('monthly-reports')
        ->name('reports.monthly-reports.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::post('/submit', 'submit')->name('submit');
            Route::get('/export', 'export')->name('export');
            Route::get('/summary', 'summary')->name('summary');
            Route::post('/generate', 'generateReport')->name('generate');
            Route::post('/generate-from-movements', 'generateReportFromMovements')->name('generate-from-movements');
        });

    // Facility Inventory Movement Reports
    Route::controller(FacilityInventoryMovementController::class)
        ->prefix('reports/facility-inventory-movements')
        ->name('reports.facility-inventory-movements.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export', 'export')->name('export');
        });

    // Reason Management Routes
    Route::controller(ReasonController::class)->prefix('reasons')->group(function () {
        Route::get('/', 'index')->name('reasons.index');
        Route::post('/store', 'store')->name('reasons.store');
        Route::delete('/destroy', 'destroy')->name('reasons.destroy');
        Route::get('/get-reasons', 'getReasons')->name('reasons.get-reasons');
    });

});

require __DIR__.'/auth.php';
