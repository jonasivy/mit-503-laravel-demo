<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Message Queue: Asynchronous order confirmation job.
 *
 * This job simulates sending an email notification by writing to a log file.
 * It is dispatched to the database queue after an order is created,
 * demonstrating asynchronous processing decoupled from the HTTP request.
 *
 * Retry config: $tries = 3, $backoff = 5 seconds between retries.
 * If all retries fail, the job moves to the failed_jobs table (dead-letter queue).
 */
class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Simulate sending an order confirmation email.
     *
     * Message Queue: Instead of actually sending an email, we write to
     * storage/logs/notifications.log. This demonstrates the job was
     * picked up from the queue and processed by the queue worker.
     */
    public function handle(): void
    {
        $message = sprintf(
            "[%s] ORDER CONFIRMATION — Order #%d | To: %s | Item: %s x%d | Total: $%s | Status: %s",
            now()->toDateTimeString(),
            $this->order->id,
            $this->order->customer_email,
            $this->order->item,
            $this->order->quantity,
            $this->order->total_price,
            $this->order->status,
        );

        // Write to dedicated notifications log channel
        Log::channel('notifications')->info($message);

        Log::info("SendOrderConfirmationJob: Processed for Order #{$this->order->id}");
    }
}
