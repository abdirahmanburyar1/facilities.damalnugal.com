<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        
        // Add formatted dates
        $data['formatted_transfer_date'] = $this->transfer_date ? Carbon::parse($this->transfer_date)->format('M d, Y') : null;
        $data['formatted_created_at'] = $this->created_at ? Carbon::parse($this->created_at)->format('M d, Y H:i') : null;
        $data['formatted_updated_at'] = $this->updated_at ? Carbon::parse($this->updated_at)->format('M d, Y H:i') : null;
        
        // Add status information
        $data['status_info'] = $this->getStatusInfo();
        
        // Add transfer direction for current user
        $data['direction'] = $this->getTransferDirection();
        
        // Add progress information
        $data['progress'] = $this->getProgressInfo();
        
        return $data;
    }
    
    /**
     * Get status information with colors and descriptions
     */
    private function getStatusInfo(): array
    {
        $statusConfig = [
            'pending' => [
                'color' => 'warning',
                'description' => 'Awaiting review',
                'icon' => 'clock'
            ],
            'reviewed' => [
                'color' => 'info',
                'description' => 'Reviewed, awaiting approval',
                'icon' => 'eye'
            ],
            'approved' => [
                'color' => 'success',
                'description' => 'Approved, ready for processing',
                'icon' => 'check'
            ],
            'rejected' => [
                'color' => 'danger',
                'description' => 'Transfer rejected',
                'icon' => 'x'
            ],
            'in_process' => [
                'color' => 'primary',
                'description' => 'Being processed',
                'icon' => 'gear'
            ],
            'dispatched' => [
                'color' => 'info',
                'description' => 'Dispatched for delivery',
                'icon' => 'truck'
            ],
            'delivered' => [
                'color' => 'success',
                'description' => 'Delivered to destination',
                'icon' => 'box'
            ],
            'received' => [
                'color' => 'success',
                'description' => 'Received and completed',
                'icon' => 'check-circle'
            ]
        ];
        
        $status = $this->status ?? 'pending';
        return $statusConfig[$status] ?? $statusConfig['pending'];
    }
    
    /**
     * Get transfer direction relative to current user
     */
    private function getTransferDirection(): string
    {
        $user = auth()->user();
        $userFacilityId = $user->facility_id;
        $userWarehouseId = $user->warehouse_id;
        
        if ($this->from_facility_id == $userFacilityId || $this->from_warehouse_id == $userWarehouseId) {
            return 'out';
        }
        
        if ($this->to_facility_id == $userFacilityId || $this->to_warehouse_id == $userWarehouseId) {
            return 'in';
        }
        
        return 'unknown';
    }
    
    /**
     * Get progress information for the transfer
     */
    private function getProgressInfo(): array
    {
        $statusOrder = ['pending', 'reviewed', 'approved', 'in_process', 'dispatched', 'delivered', 'received'];
        $currentStatus = $this->status ?? 'pending';
        $currentIndex = array_search($currentStatus, $statusOrder);
        $totalSteps = count($statusOrder);
        
        $progress = $currentIndex !== false ? (($currentIndex + 1) / $totalSteps) * 100 : 0;
        
        return [
            'percentage' => round($progress),
            'current_step' => $currentIndex + 1,
            'total_steps' => $totalSteps,
            'status_order' => $statusOrder
        ];
    }
}
