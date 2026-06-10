<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderProcessingFailed extends Notification
{
    public function __construct(private Order $order) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {

        $failedItems = OrderItem::where('order_id', $this->order->id)
            ->where('status', OrderStatus::FAILED)
            ->get(['product_id', 'failed_reason', 'source']);

        $mail = (new MailMessage)
            ->subject("❌ Order Failure Report – Order #{$this->order->order_id_2game}")
            ->cc($notifiable->ccEmails())
            ->greeting('Order Processing Failure Report')
            ->line("Order ID: {$this->order->order_id_2game}")
            ->line("Internal ID: {$this->order->id}")
            ->line("Failed Items Count: {$failedItems->count()}")
            ->line('-------------------------------------');

        foreach ($failedItems as $item) {

            $sourceName = match ((int) $item->source) {
                1 => 'Ztorm',
                2 => 'Incomm',
                default => 'Unknown',
            };

            $reason = is_string($item->failed_reason)
                ? $item->failed_reason
                : json_encode($item->failed_reason);

            $reason = preg_replace(
                '/([?&]password=)[^&]+/',
                '$1[REDACTED]',
                $reason
            );

            $mail->line("• Product: {$item->product_id}")
                ->line("  Source: {$sourceName}")
                ->line("  Reason: {$reason}")
                ->line('');
        }


        return $mail->line('Please investigate the above failed items.');
    }
}
