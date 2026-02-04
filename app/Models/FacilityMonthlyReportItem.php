<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityMonthlyReportItem extends Model
{
    use HasFactory;

    protected $table = 'facility_monthly_report_items';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Automatically recalculate closing balance when relevant fields are updated
        static::saving(function (FacilityMonthlyReportItem $item) {
            $item->closing_balance = $item->calculateClosingBalance();
        });
    }

    protected $fillable = [
        'parent_id',
        'product_id',
        'opening_balance',
        'stock_received',
        'stock_issued',
        'positive_adjustments',
        'negative_adjustments',
        'closing_balance',
        'stockout_days',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'stock_received' => 'decimal:2',
        'stock_issued' => 'decimal:2',
        'positive_adjustments' => 'decimal:2',
        'negative_adjustments' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'stockout_days' => 'integer',
    ];

    /**
     * Get the monthly report that owns this item
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(FacilityMonthlyReport::class, 'parent_id');
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate closing balance using LMIS formula
     * Closing Balance = Beginning Balance + Qty Received - Qty Consumed + Positive Adjustments - Negative Adjustments
     */
    public function calculateClosingBalance(): float
    {
        return $this->opening_balance 
             + $this->stock_received 
             - $this->stock_issued 
             + $this->positive_adjustments 
             - $this->negative_adjustments;
    }

    /**
     * Update closing balance automatically
     */
    public function updateClosingBalance(): void
    {
        $this->closing_balance = $this->calculateClosingBalance();
        $this->save();
    }

    /**
     * Check if item has stock movement
     */
    public function hasMovement(): bool
    {
        return $this->stock_received > 0 || $this->stock_issued > 0 || 
               $this->positive_adjustments > 0 || $this->negative_adjustments > 0;
    }

    /**
     * Check if item has stockout
     */
    public function hasStockout(): bool
    {
        return $this->stockout_days > 0;
    }

    /**
     * Get net movement (received - issued)
     */
    public function getNetMovementAttribute(): float
    {
        return $this->stock_received - $this->stock_issued;
    }

    /**
     * Get total adjustments (positive - negative)
     */
    public function getTotalAdjustmentsAttribute(): float
    {
        return $this->positive_adjustments - $this->negative_adjustments;
    }

    /**
     * Scope for items with movement
     */
    public function scopeWithMovement($query)
    {
        return $query->where(function ($q) {
            $q->where('stock_received', '>', 0)
              ->orWhere('stock_issued', '>', 0)
              ->orWhere('positive_adjustments', '>', 0)
              ->orWhere('negative_adjustments', '>', 0);
        });
    }

    /**
     * Scope for items with stockout
     */
    public function scopeWithStockout($query)
    {
        return $query->where('stockout_days', '>', 0);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }


}
