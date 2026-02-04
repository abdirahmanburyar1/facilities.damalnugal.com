<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class TransferCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The transfer instance.
     *
     * @var \App\Models\Transfer
     */
    protected $transfer;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Transfer $transfer
     * @return void
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $sourceType = $this->transfer->from_warehouse_id ? 'Warehouse' : 'Facility';
        $sourceName = $this->transfer->from_warehouse_id 
            ? $this->transfer->fromWarehouse->name 
            : $this->transfer->fromFacility->name;

        $destinationType = $this->transfer->to_warehouse_id ? 'Warehouse' : 'Facility';
        $destinationName = $this->transfer->to_warehouse_id 
            ? $this->transfer->toWarehouse->name 
            : $this->transfer->toFacility->name;

        $transferUrl = url(route('transfers.show', $this->transfer->id));
        
        $itemsTable = $this->getItemsTable();

        return (new MailMessage)
            ->subject('New Transfer Request: ' . $this->transfer->transferID)
            ->greeting('Hello!')
            ->line('A new transfer has been created and requires your attention.')
            ->line(new HtmlString('<strong>Transfer ID:</strong> ' . $this->transfer->transferID))
            ->line(new HtmlString('<strong>From:</strong> ' . $sourceType . ' - ' . $sourceName))
            ->line(new HtmlString('<strong>To:</strong> ' . $destinationType . ' - ' . $destinationName))
            ->line(new HtmlString('<strong>Total Quantity:</strong> ' . $this->transfer->quantity))
            ->line(new HtmlString('<strong>Status:</strong> ' . ucfirst($this->transfer->status)))
            ->line(new HtmlString('<strong>Created Date:</strong> ' . Carbon::parse($this->transfer->transfer_date)->format('d/m/Y')))
            ->line(new HtmlString('<strong>Items:</strong>'))
            ->line(new HtmlString($itemsTable))
            ->action('View Transfer Details', $transferUrl)
            ->line('Please review and take appropriate action on this transfer request.')
            ->salutation('Thank you for using our Warehouse Management System!');
    }

    /**
     * Generate an HTML table of transfer items.
     *
     * @return string
     */
    protected function getItemsTable(): string
    {
        $items = $this->transfer->items()->with('product')->get();
        
        $tableStyle = 'border-collapse: collapse; width: 100%; margin-bottom: 20px;';
        $thStyle = 'border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2; text-align: left;';
        $tdStyle = 'border: 1px solid #ddd; padding: 8px;';
        
        $table = "<table style='{$tableStyle}'>";
        $table .= "<tr>"
               . "<th style='{$thStyle}'>Product</th>"
               . "<th style='{$thStyle}'>Quantity</th>"
               . "<th style='{$thStyle}'>Batch Number</th>"
               . "<th style='{$thStyle}'>Expiry Date</th>"
               . "</tr>";
        
        foreach ($items as $item) {
            $expiryDate = $item->expire_date ? date('Y-m-d', strtotime($item->expire_date)) : 'N/A';
            
            $table .= "<tr>"
                   . "<td style='{$tdStyle}'>" . ($item->product->name ?? 'Unknown Product') . "</td>"
                   . "<td style='{$tdStyle}'>" . $item->quantity . "</td>"
                   . "<td style='{$tdStyle}'>" . $item->batch_number . "</td>"
                   . "<td style='{$tdStyle}'>" . $expiryDate . "</td>"
                   . "</tr>";
        }
        
        $table .= "</table>";
        
        return $table;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transfer_id' => $this->transfer->id,
            'transfer_code' => $this->transfer->transferID,
            'status' => $this->transfer->status,
            'quantity' => $this->transfer->quantity,
        ];
    }
}
