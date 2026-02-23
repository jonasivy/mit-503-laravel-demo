<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

/**
 * System Integration: Listens to the OrderPlaced event.
 *
 * This listener is automatically invoked when OrderPlaced is fired.
 * It logs a notification to storage/logs/notifications.log,
 * demonstrating the event-driven (observer) pattern.
 *
 * The OrderService does not call this directly — it only fires
 * the event. Laravel's event system handles the rest.
 */
class SendNotificationListener
{
    /**
     * Handle the OrderPlaced event.
     *
     * System Integration: Event-driven — this code runs automatically
     * when OrderPlaced is dispatched, without the dispatcher knowing
     * about this listener.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $message = sprintf(
            "[%s] EVENT NOTIFICATION — Order #%d placed by %s | Item: %s x%d | Total: $%s",
            now()->toDateTimeString(),
            $order->id,
            $order->customer_name,
            $order->item,
            $order->quantity,
            $order->total_price,
        );

        Log::channel('notifications')->info($message);

        Log::info("SendNotificationListener: Logged notification for Order #{$order->id}");
    }
}
