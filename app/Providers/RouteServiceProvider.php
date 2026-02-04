<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\FacilityInventory;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Custom route model binding for FacilityInventory to ensure facility isolation
        Route::bind('inventory', function ($value) {
            return FacilityInventory::where('id', $value)
                ->where('facility_id', auth()->user()->facility_id)
                ->firstOrFail();
        });

        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    }
}
