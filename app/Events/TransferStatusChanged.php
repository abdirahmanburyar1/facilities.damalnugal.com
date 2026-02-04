<?php

namespace App\Events;

use App\Models\Transfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferStatusChanged
{
    use Dispatchable, SerializesModels;

    public $transfer;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    /**
     * Create a new event instance.
     *
     * @param  Transfer  $transfer
     * @param  string  $oldStatus
     * @param  string  $newStatus
     * @param  int  $changedBy
     * @return void
     */
    public function __construct(Transfer $transfer, string $oldStatus, string $newStatus, int $changedBy)
    {
        $this->transfer = $transfer;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }


}
