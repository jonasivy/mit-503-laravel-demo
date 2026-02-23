<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * System Integration: OrderPlaced event.
 *
 * Fired after an order is successfully created and persisted.
 * Listeners can react to this event without the OrderService
 * needing to know about them — this is event-driven decoupling.
 */
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}
