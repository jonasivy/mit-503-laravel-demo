<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\UpdateInventoryJob;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SOA: OrderService — Orchestrates the order creation workflow.
 *
 * This service contains ALL business logic for order processing.
 * The controller remains thin and only handles HTTP concerns.
 *
 * Workflow:
 * 1. SYNCHRONOUS: Calls InventoryService to verify stock before saving
 * 2. PERSIST: Creates the order in the database
 * 3. ASYNCHRONOUS: Dispatches queue jobs and fires events (added in later PRs)
 */
class OrderService
{
    /**
     * SOA: Constructor injection — services are injected, not instantiated.
     * This enables loose coupling and testability.
     */
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create a new order after validating inventory availability.
     *
     * SOA: OrderService delegates to InventoryService SYNCHRONOUSLY.
     * If the item is out of stock, an exception is thrown and the
     * controller returns a 422 response. No order is created.
     *
     * @param array{customer_name: string, customer_email: string, item: string, quantity: int, total_price: float} $data Validated order data
     * @return Order The created order
     *
     * @throws \RuntimeException When the requested item is out of stock
     */
    public function createOrder(array $data): Order
    {
        // SOA: Synchronous call — InventoryService checks stock BEFORE we save
        if (!$this->inventoryService->checkAvailability($data['item'], $data['quantity'])) {
            Log::warning("OrderService: Order rejected — '{$data['item']}' is out of stock");
            throw new \RuntimeException("Item '{$data['item']}' is out of stock or insufficient quantity.");
        }

        // SOA: Reserve inventory synchronously
        $this->inventoryService->reserve($data['item'], $data['quantity']);

        // Persist the order
        $order = Order::create([
            'customer_name'  => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'item'           => $data['item'],
            'quantity'        => $data['quantity'],
            'total_price'    => $data['total_price'],
            'status'         => 'pending',
        ]);

        Log::info("OrderService: Order #{$order->id} created for {$order->customer_name}");

        // Prepare notification payload (SOA: NotificationService builds it, doesn't send)
        $this->notificationService->prepareOrderConfirmation($order);

        // Message Queue: Dispatch async jobs AFTER the order is persisted
        // These run in the background via `php artisan queue:work`
        SendOrderConfirmationJob::dispatch($order);
        UpdateInventoryJob::dispatch($order);

        Log::info("OrderService: Dispatched async jobs for Order #{$order->id}");

        // System Integration: Fire event — listeners react without OrderService knowing about them
        OrderPlaced::dispatch($order);

        // System Integration: Outbound webhook — POST order data to external system
        $this->sendWebhook($order);

        return $order;
    }

    /**
     * Retrieve a paginated list of orders.
     *
     * @param int $perPage Number of items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listOrders(int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::latest()->paginate($perPage);
    }

    /**
     * Find a single order by ID.
     *
     * @param int $id The order ID
     * @return Order
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrder(int $id): Order
    {
        return Order::findOrFail($id);
    }

    /**
     * Send an outbound webhook notification to a configured URL.
     *
     * System Integration: Demonstrates outbound HTTP integration.
     * The WEBHOOK_URL is configurable via .env. A local /api/v1/webhook-test
     * route is provided for demo purposes. Failures are logged but do not
     * block the order creation response.
     */
    private function sendWebhook(Order $order): void
    {
        $webhookUrl = config('services.webhook.url');

        if (empty($webhookUrl)) {
            Log::info("OrderService: No WEBHOOK_URL configured, skipping webhook");
            return;
        }

        try {
            $payload = $this->notificationService->prepareWebhookPayload($order);
            $response = Http::timeout(5)->post($webhookUrl, $payload);

            Log::info("OrderService: Webhook sent to {$webhookUrl} — status: {$response->status()}");
        } catch (\Exception $e) {
            // Webhook failure should not block order creation
            Log::warning("OrderService: Webhook failed — {$e->getMessage()}");
        }
    }

    /**
     * Update the status of an existing order.
     *
     * @param int    $id     The order ID
     * @param string $status The new status (pending, confirmed, failed)
     * @return Order The updated order
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateOrderStatus(int $id, string $status): Order
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => $status]);

        Log::info("OrderService: Order #{$order->id} status updated to '{$status}'");

        return $order;
    }
}
