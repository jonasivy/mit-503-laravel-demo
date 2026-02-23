<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * SOA: NotificationService — Single responsibility for notification preparation.
 *
 * This service prepares notification payloads but does NOT send them directly.
 * Actual delivery is handled asynchronously by queue jobs (SendOrderConfirmationJob)
 * or event listeners (SendNotificationListener). This separation ensures the
 * notification logic is decoupled from the delivery mechanism.
 */
class NotificationService
{
    /**
     * Prepare an order confirmation notification payload.
     *
     * SOA: This method only builds the payload — it does not perform I/O
     * (no emails, no HTTP calls). The caller decides how to deliver it
     * (sync, async, queue, webhook, etc.).
     *
     * @param Order $order The order to build a notification for
     * @return array{to: string, subject: string, body: string, order_id: int} The notification payload
     */
    public function prepareOrderConfirmation(Order $order): array
    {
        $payload = [
            'to' => $order->customer_email,
            'subject' => "Order #{$order->id} Confirmation",
            'body' => "Dear {$order->customer_name}, your order for {$order->quantity}x {$order->item} "
                     . "(total: \${$order->total_price}) has been received and is now {$order->status}.",
            'order_id' => $order->id,
        ];

        Log::info("NotificationService: Prepared confirmation payload for Order #{$order->id}");

        return $payload;
    }

    /**
     * Prepare a webhook payload for external system integration.
     *
     * System Integration: This payload is sent via HTTP POST to a configurable
     * WEBHOOK_URL, demonstrating outbound integration with external services.
     *
     * @param Order $order The order to build a webhook payload for
     * @return array{event: string, order_id: int, customer_name: string, customer_email: string, item: string, quantity: int, total_price: string, status: string, created_at: string|null} The webhook payload
     */
    public function prepareWebhookPayload(Order $order): array
    {
        return [
            'event' => 'order.placed',
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'item' => $order->item,
            'quantity' => $order->quantity,
            'total_price' => $order->total_price,
            'status' => $order->status,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
