<?php

namespace App\Jobs;

use App\Models\InventoryLog;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Message Queue: Asynchronous inventory update job.
 *
 * This job writes a record to the inventory_logs table after an order
 * is created. It runs asynchronously via the database queue, demonstrating
 * how post-order processing can be offloaded from the HTTP request cycle.
 *
 * Retry config: $tries = 3. If all retries fail, moves to failed_jobs.
 */
class UpdateInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Write an inventory deduction log record.
     *
     * Message Queue: This persists the inventory change to the database,
     * proving the job was processed by the queue worker. The inventory_logs
     * table serves as an audit trail for all inventory movements.
     */
    public function handle(): void
    {
        InventoryLog::create([
            'order_id'         => $this->order->id,
            'item'             => $this->order->item,
            'quantity_deducted' => $this->order->quantity,
            'processed_at'     => now(),
        ]);

        Log::info("UpdateInventoryJob: Inventory log created for Order #{$this->order->id} ({$this->order->quantity}x {$this->order->item})");
    }
}
