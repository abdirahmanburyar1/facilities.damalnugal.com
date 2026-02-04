<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\BackOrder;

class BackOrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'packing_list_id',
        'order_item_id',
        'transfer_item_id',
        'barcode',
        'batch_number',
        'expiry_date',
        'uom',
        'unit_cost',
        'total_cost',
        'product_id',
        'quantity',
        'status',
        'note',
        'performed_by',
        'back_order_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function backOrder(): BelongsTo
    {
        return $this->belongsTo(BackOrder::class);
    }
}
