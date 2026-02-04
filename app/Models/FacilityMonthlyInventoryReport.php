<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityMonthlyInventoryReport extends Model
{
    use HasFactory;

    protected $table = 'facility_monthly_inventory_reports';

    protected $fillable = [
        'facility_id',
        'product_id',
        'report_year',
        'report_month',
        'opening_balance',
        'stock_received',
        'stock_issued',
        'positive_adjustments',
        'negative_adjustments',
        'closing_balance',
        'stockout_days',
        'status',
        'comments',
        'submitted_at',
        'approved_at',
        'submitted_by',
        'approved_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'stock_received' => 'decimal:2',
        'stock_issued' => 'decimal:2',
        'positive_adjustments' => 'decimal:2',
        'negative_adjustments' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'report_year' => 'integer',
        'report_month' => 'integer',
        'stockout_days' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the facility that owns the report
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the product for this report
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who submitted the report
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who approved the report
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
     * Get formatted report period
     */
    public function getReportPeriodAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[$this->report_month] . ' ' . $this->report_year;
    }

    /**
     * Get formatted month name
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[$this->report_month] ?? 'Unknown';
    }

    /**
     * Check if report can be edited
     */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if report can be submitted
     */
    public function canSubmit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if report can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Submit the report
     */
    public function submit(): void
    {
        if ($this->canSubmit()) {
            $this->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Approve the report
     */
    public function approve(): void
    {
        if ($this->canApprove()) {
            $this->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Scope for specific facility
     */
    public function scopeForFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('report_year', $year)->where('report_month', $month);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for current user's facility
     */
    public function scopeForCurrentFacility($query)
    {
        return $query->where('facility_id', auth()->user()->facility_id);
    }

    /**
     * Scope for recent reports (last 12 months)
     */
    public function scopeRecent($query)
    {
        $twelveMonthsAgo = now()->subMonths(12);
        return $query->where(function ($q) use ($twelveMonthsAgo) {
            $q->where('report_year', '>', $twelveMonthsAgo->year)
              ->orWhere(function ($q2) use ($twelveMonthsAgo) {
                  $q2->where('report_year', $twelveMonthsAgo->year)
                     ->where('report_month', '>=', $twelveMonthsAgo->month);
              });
        });
    }
}
