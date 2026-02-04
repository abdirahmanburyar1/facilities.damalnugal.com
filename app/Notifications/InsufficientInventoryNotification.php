<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InsufficientInventoryNotification extends Notification
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mohDispenseNumber = $this->data['moh_dispense_number'];
        $facilityName = $this->data['facility_name'];
        $insufficientItems = $this->data['insufficient_items'];
        $totalShortage = $this->data['total_shortage'];

        $mailMessage = (new MailMessage)
            ->subject('Insufficient Inventory Alert - MOH Dispense Processing')
            ->greeting('Hello!')
            ->line("MOH Dispense processing has been stopped due to insufficient inventory.")
            ->line("**MOH Dispense Number:** {$mohDispenseNumber}")
            ->line("**Facility:** {$facilityName}")
            ->line("**Total Shortage:** {$totalShortage} units");

        // Add details for each insufficient item
        if (!empty($insufficientItems)) {
            $mailMessage->line("**Items with insufficient inventory:**");
            foreach ($insufficientItems as $item) {
                $mailMessage->line("- **{$item['product_name']}**: Required {$item['required_quantity']}, Available {$item['available_quantity']}, Shortage {$item['shortage']}");
            }
        }

        $mailMessage
            ->line("Please restock the required items and try processing the MOH dispense again.")
            ->action('View MOH Dispense', url('/moh-dispenses'))
            ->line('Thank you for using our system!');

        return $mailMessage;
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'insufficient_inventory',
            'title' => 'Insufficient Inventory Alert',
            'message' => "MOH Dispense {$this->data['moh_dispense_number']} processing stopped due to insufficient inventory.",
            'data' => $this->data,
            'action_url' => '/moh-dispenses',
            'created_at' => now(),
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'insufficient_inventory',
            'title' => 'Insufficient Inventory Alert',
            'message' => "MOH Dispense {$this->data['moh_dispense_number']} processing stopped due to insufficient inventory.",
            'data' => $this->data,
        ];
    }
}
