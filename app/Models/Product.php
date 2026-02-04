<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\Inventory;
use App\Models\Supply;
use App\Models\SupplyItem;
use App\Models\SubCategory;
use App\Models\MonthlyConsumptionReport;
use App\Models\MonthlyConsumptionItem;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'productID',
        'name',
        'category_id',
        'dosage_id',
        // 'movement',
        'is_active',
        'tracert_type'
    ];

    protected $casts = [
        'tracert_type' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Find the highest productID in the database
            $maxProductId = self::max('productID');
            
            // If there are existing products, increment the highest productID
            if ($maxProductId) {
                $nextId = (int)$maxProductId + 1;
            } else {
                // Start from 1 if no products exist
                $nextId = 1;
            }
            
            // Format as 6-digit number with leading zeros
            $product->productID = str_pad($nextId, 6, '0', STR_PAD_LEFT);
        });
    }



    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function dosage()
    {
        return $this->belongsTo(Dosage::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * Get the supply items that contain this product.
     */
    public function supplyItems()
    {
        return $this->hasMany(SupplyItem::class);
    }

    /**
     * Get the supplies that contain this product.
     */
    public function supplies()
    {
        return $this->belongsToMany(Supply::class, 'supply_items')
            ->withPivot(['quantity', 'status'])
            ->withTimestamps();
    }

    // reorderLevel
    public function reorderLevel()
    {
        return $this->hasOne(ReorderLevel::class);
    }

    /**
     * Get the inventories for the product.
     */
    public function inventories()
    {
        return $this->hasMany(FacilityInventory::class);
    }

    public function items(){
        return $this->hasMany(FacilityInventoryItem::class);
    }

    public function eligible(){
        return $this->hasMany(EligibleItem::class);
    }

    public function facilityInventories(){
        return $this->hasMany(FacilityInventory::class);
    }

    /**
     * Get the monthly consumption reports for this product.
     */
    public function monthlyConsumptionReports()
    {
        return $this->hasManyThrough(
            MonthlyConsumptionReport::class,
            MonthlyConsumptionItem::class,
            'product_id', // Foreign key on facility_monthly_report_items table
            'id', // Local key on facility_monthly_reports table
            'id', // Local key on products table
            'parent_id' // Foreign key on facility_monthly_report_items table
        );
    }

    /**
     * Get the monthly consumption items for this product.
     */
    public function monthlyConsumptionItems()
    {
        return $this->hasMany(MonthlyConsumptionItem::class);
    }

    /**
     * Get the monthly consumption items for this product in a specific facility.
     */
    public function monthlyConsumptionItemsForFacility()
    {
        return $this->monthlyConsumptionItems()
            ->join('monthly_consumption_reports', 'monthly_consumption_items.parent_id', '=', 'monthly_consumption_reports.id')
            ->where('monthly_consumption_reports.facility_id', auth()->user()->facility_id);
    }

    /**
     * Get AMC data for a specific facility with detailed information
     */
    public function getAMCDataForFacility()
    {
        $facilityId = auth()->user()->facility_id;
        $amc = $this->calculateAMC($facilityId);
        $bufferStock = $this->calculateBufferStock($facilityId);
        $reorderLevel = $this->calculateReorderLevel($facilityId);
        
        return [
            'amc' => $amc,
            'buffer_stock' => $bufferStock,
            'reorder_level' => $reorderLevel,
            'facility_id' => $facilityId
        ];
    }

    /**
     * Check if there are any monthly consumption reports available for this product
     */
    public function hasConsumptionData($facilityId = null)
    {
        try {
            $query = $this->monthlyConsumptionItems()
                ->join('facility_monthly_reports', 'facility_monthly_report_items.parent_id', '=', 'facility_monthly_reports.id')
                ->where('facility_monthly_reports.status', 'approved')
                ->where('facility_monthly_report_items.stock_issued', '>', 0);
            
            if ($facilityId) {
                $query->where('facility_monthly_reports.facility_id', $facilityId);
            }
            
            $count = $query->count();
            
            \Log::info("Product {$this->id} ({$this->name}) consumption data check:", [
                'facility_id' => $facilityId,
                'total_consumptions' => $count,
                'has_sufficient_data' => $count >= 3
            ]);
            
            return $count >= 3;
            
        } catch (\Exception $e) {
            \Log::warning("Error checking consumption data for product {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate a fallback reorder level when no consumption data is available
     * This method provides reasonable defaults based on current inventory
     */
    public function calculateFallbackReorderLevel($facilityId = null)
    {
        try {
            // Check if this product has ANY consumption data for the facility
            $hasAnyConsumption = $this->monthlyConsumptionItems()
                ->join('monthly_consumption_reports', 'monthly_consumption_items.parent_id', '=', 'monthly_consumption_reports.id')
                ->where('monthly_consumption_reports.facility_id', $facilityId)
                ->where('monthly_consumption_items.quantity', '>', 0)
                ->exists();
            
            // If no consumption data exists at all, return 0 (not a static value)
            if (!$hasAnyConsumption) {
                \Log::info("Product {$this->id} ({$this->name}) has no consumption data for facility {$facilityId}, returning 0");
                return 0;
            }
            
            // If we have some consumption data but less than 3 months, 
            // we could potentially use a different fallback strategy
            // For now, return 0 to indicate insufficient data
            \Log::info("Product {$this->id} ({$this->name}) has some consumption data but insufficient for AMC calculation, returning 0");
            return 0;
            
        } catch (\Exception $e) {
            \Log::warning("Error calculating fallback reorder level for product {$this->id}: " . $e->getMessage());
            return 0; // Return 0 on error, not a static value
        }
    }

    /**
     * Calculate AMC using the AMC service with proper 70% deviation screening
     */
    public function calculateAMC($facilityId = null)
    {
        try {
            if (!$facilityId) {
                $facilityId = auth()->user()->facility_id ?? null;
            }
            
            if (!$facilityId) {
                return 0;
            }
            
            $amcService = new \App\Services\AMCService();
            $result = $amcService->calculateScreenedAMC($facilityId, $this->id);
            
            return [
                'amc' => $result['amc'],
                'max_amc' => $result['max_amc'],
                'months_used' => $result['eligible_months_count'],
                'selected_months' => []
            ];
            
        } catch (\Exception $e) {
            \Log::warning("Error calculating AMC for product {$this->id}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate buffer stock: (Max AMC - AMC) × 0.46
     * Max AMC is the highest value from the selected months that passed screening
     */
    public function calculateBufferStock($facilityId = null)
    {
        try {
            $amc = $this->calculateAMC($facilityId);
            if ($amc == 0) {
                return 0;
            }

            // Get the selected months that passed screening (same logic as calculateAMC)
            $consumptionsWithMonth = $this->monthlyConsumptionItems()
                ->join('facility_monthly_reports', 'monthly_consumption_items.parent_id', '=', 'facility_monthly_reports.id')
                ->where('facility_monthly_reports.status', 'approved') // Only use approved reports
                ->where('monthly_consumption_items.stock_issued', '>', 0) // Use stock_issued as consumption
                ->orderBy('facility_monthly_reports.report_period', 'desc') // Most recent first
                ->get(['facility_monthly_reports.report_period as month_year', 'monthly_consumption_items.stock_issued as quantity']);

            if ($consumptionsWithMonth->count() < 3) {
                return 0;
            }

            // Use the same screening logic to get selected months
            $quantities = $consumptionsWithMonth->pluck('quantity')->values();
            $months = $consumptionsWithMonth->pluck('month_year')->values();
            
            $selectedMonths = [];
            $passedMonths = [];
            $failedMonths = [];
            
            // Initial selection: 3 most recent months
            for ($i = 0; $i < 3; $i++) {
                $selectedMonths[] = [
                    'month' => $months[$i],
                    'quantity' => $quantities[$i]
                ];
            }
            
            $attempt = 1;
            $maxAttempts = 10;
            
            while ($attempt <= $maxAttempts) {
                $average = collect($selectedMonths)->avg('quantity');
                $allPassed = true;
                $newPassedMonths = [];
                $newFailedMonths = [];
                
                foreach ($selectedMonths as $monthData) {
                    $quantity = $monthData['quantity'];
                    $deviation = abs($quantity - $average) / $average * 100;
                    
                    if ($deviation <= 70) {
                        $newPassedMonths[] = $monthData;
                    } else {
                        $newFailedMonths[] = $monthData;
                        $allPassed = false;
                    }
                }
                
                foreach ($newPassedMonths as $monthData) {
                    if (!collect($passedMonths)->contains('month', $monthData['month'])) {
                        $passedMonths[] = $monthData;
                    }
                }
                
                foreach ($newFailedMonths as $monthData) {
                    if (!collect($failedMonths)->contains('month', $monthData['month'])) {
                        $failedMonths[] = $monthData;
                    }
                }
                
                if ($allPassed) {
                    break;
                }
                
                if (count($passedMonths) >= 3) {
                    $selectedMonths = array_slice($passedMonths, 0, 3);
                    break;
                }
                
                $newSelection = [];
                foreach ($passedMonths as $monthData) {
                    $newSelection[] = $monthData;
                }
                
                $monthIndex = 0;
                while (count($newSelection) < 3 && $monthIndex < count($quantities)) {
                    $monthData = [
                        'month' => $months[$monthIndex],
                        'quantity' => $quantities[$monthIndex]
                    ];
                    
                    $alreadySelected = collect($newSelection)->contains('month', $monthData['month']);
                    $isFailed = collect($failedMonths)->contains('month', $monthData['month']);
                    
                    if (!$alreadySelected && !$isFailed) {
                        $newSelection[] = $monthData;
                    }
                    
                    $monthIndex++;
                }
                
                $selectedMonths = $newSelection;
                $attempt++;
            }
            
            if (count($selectedMonths) >= 3) {
                // Find the maximum value from selected months
                $maxAMC = max(array_column($selectedMonths, 'quantity'));
                
                // Calculate buffer stock: (Max AMC - AMC) × 0.46
                $bufferStock = ($maxAMC - $amc) * 0.46;
                return round(max(0, $bufferStock), 2);
            }
            
            return 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate reorder level: (AMC × 0.46) + Buffer Stock
     */
    public function calculateReorderLevel($facilityId = null)
    {
        try {
            $amc = $this->calculateAMC($facilityId);
            if ($amc == 0) {
                return 0;
            }
            
            $bufferStock = $this->calculateBufferStock($facilityId);
            
            // Calculate reorder level: (AMC × 0.46) + Buffer Stock
            $reorderLevel = ($amc * 0.46) + $bufferStock;
            return round($reorderLevel, 2);
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Fast and simple method to calculate AMC, Buffer Stock, and Reorder Level
     * This replaces the complex percentage deviation screening with a simple approach
     */
    public function calculateInventoryMetrics($facilityId = null)
    {
        try {
            // Use the calculateAMC method with proper 70% deviation screening
            $amcData = $this->calculateAMC($facilityId);
            
            if (is_array($amcData) && isset($amcData['amc']) && $amcData['amc'] > 0) {
                $amc = $amcData['amc'];
                $maxAmc = $amcData['max_amc'];
                
                // Calculate buffer stock: (Max AMC - AMC) × 0.46
                $bufferStock = round(($maxAmc - $amc) * 0.46, 2);
                
                // Calculate reorder level: (AMC × 0.46) + Buffer Stock
                $reorderLevel = ($amc * 0.46) + $bufferStock;
                $reorderLevel = round($reorderLevel, 2);
                
                \Log::info("Product {$this->id} ({$this->name}) calculated metrics:", [
                    'amc' => $amc,
                    'max_amc' => $maxAmc,
                    'buffer_stock' => $bufferStock,
                    'reorder_level' => $reorderLevel,
                    'months_used' => $amcData['months_used'] ?? 0
                ]);
                
                return [
                    'amc' => $amc,
                    'buffer_stock' => $bufferStock,
                    'reorder_level' => $reorderLevel
                ];
            } else {
                // Fallback if calculateAMC returns 0 or invalid data
                $fallbackReorderLevel = $this->calculateFallbackReorderLevel($facilityId);
                
                return [
                    'amc' => 0,
                    'buffer_stock' => 0,
                    'reorder_level' => $fallbackReorderLevel
                ];
            }

        } catch (\Exception $e) {
            \Log::error("Error calculating inventory metrics for product {$this->id}: " . $e->getMessage());
            
            $fallbackReorderLevel = $this->calculateFallbackReorderLevel($facilityId);
            
            return [
                'amc' => 0,
                'buffer_stock' => 0,
                'reorder_level' => $fallbackReorderLevel
            ];
        }
    }

    /**
     * Get the inventory structure for frontend
     */
    public function getInventoryStructureAttribute()
    {
        // Get inventory items directly for this product
        $inventoryItems = $this->items;
        
        // Calculate all inventory metrics in one optimized call
        $metrics = $this->calculateInventoryMetrics();
        
        // Calculate the current status based on total quantity and reorder level
        // EXACTLY matching the frontend getInventoryStatus logic
        $totalQuantity = $inventoryItems->sum('quantity');
        $reorderLevel = $metrics['reorder_level'];
        
        $status = 'in_stock'; // default
        
        // Check if completely out of stock first
        if ($totalQuantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($reorderLevel <= 0) {
            // No reorder level set, default to in stock
            $status = 'in_stock';
        } else {
            // Calculate the low stock threshold (reorder level + 30%)
            $lowStockThreshold = $reorderLevel * 1.3;
            
            if ($totalQuantity <= $reorderLevel) {
                // Items at or below reorder level (1 to 9,000 in your example)
                $status = 'low_stock_reorder_level';
            } elseif ($totalQuantity <= $lowStockThreshold) {
                // Items between reorder level and reorder level + 30% (9,001 to 11,700 in your example)
                $status = 'low_stock';
            } else {
                // Items above reorder level + 30% (above 11,700 in your example)
                $status = 'in_stock';
            }
        }
        
        return [
            'id' => $this->id,
            'product_id' => $this->id,
            'items' => $inventoryItems, // This will be empty array if no items exist
            'amc' => $metrics['amc'],
            'buffer_stock' => $metrics['buffer_stock'],
            'reorder_level' => $metrics['reorder_level'],
            'status' => $status, // Add calculated status
            'product' => [
                'id' => $this->id,
                'name' => $this->name,
                'category' => [
                    'id' => $this->category->id ?? null,
                    'name' => $this->category->name ?? null
                ],
                'dosage' => [
                    'id' => $this->dosage->id ?? null,
                    'name' => $this->dosage->name ?? null
                ]
            ]
        ];
    }


}
