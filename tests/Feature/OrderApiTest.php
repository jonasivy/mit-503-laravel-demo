<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\UpdateInventoryJob;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for the Order Processing API.
 *
 * These tests verify all four course topics through working API calls:
 * - API Design: REST endpoints, status codes, validation, resource format
 * - SOA: Service layer handles business logic (tested via API behavior)
 * - System Integration: Events, webhooks, sync/async patterns
 * - Middleware & Queues: Logging, async job dispatching
 */
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // API DESIGN: RESTful endpoints with proper HTTP semantics
    // ---------------------------------------------------------------

    public function test_can_create_order_returns_201(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'item'           => 'laptop',
            'quantity'       => 2,
            'total_price'    => 2499.98,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'item',
                    'quantity',
                    'status',
                    'total_price',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.customer_name', 'John Doe')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'item'           => 'laptop',
            'quantity'       => 2,
        ]);
    }

    public function test_can_list_orders_with_pagination(): void
    {
        Order::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/orders?limit=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_get_single_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.customer_name', $order->customer_name);
    }

    public function test_get_nonexistent_order_returns_404(): void
    {
        $response = $this->getJson('/api/v1/orders/9999');

        $response->assertStatus(404);
    }

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/v1/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    }

    // ---------------------------------------------------------------
    // API DESIGN: Validation (StoreOrderRequest)
    // ---------------------------------------------------------------

    public function test_create_order_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_email',
                'item',
                'quantity',
                'total_price',
            ]);
    }

    public function test_create_order_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'Test',
            'customer_email' => 'not-an-email',
            'item'           => 'laptop',
            'quantity'       => 1,
            'total_price'    => 99.99,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_create_order_validates_quantity_minimum(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'Test',
            'customer_email' => 'test@test.com',
            'item'           => 'laptop',
            'quantity'       => 0,
            'total_price'    => 99.99,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_update_order_validates_status_enum(): void
    {
        $order = Order::factory()->create();

        $response = $this->patchJson("/api/v1/orders/{$order->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // SOA: InventoryService rejects out-of-stock orders (sync check)
    // ---------------------------------------------------------------

    public function test_out_of_stock_item_returns_422(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'Test',
            'customer_email' => 'test@test.com',
            'item'           => 'unicorn',
            'quantity'       => 1,
            'total_price'    => 99.99,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', "Item 'unicorn' is out of stock or insufficient quantity.");

        $this->assertDatabaseMissing('orders', [
            'customer_name' => 'Test',
        ]);
    }

    // ---------------------------------------------------------------
    // SYSTEM INTEGRATION: Webhook test endpoint
    // ---------------------------------------------------------------

    public function test_webhook_test_endpoint_receives_payload(): void
    {
        $payload = [
            'event'    => 'order.placed',
            'order_id' => 1,
        ];

        $response = $this->postJson('/api/v1/webhook-test', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'received');
    }

    // ---------------------------------------------------------------
    // MESSAGE QUEUES: Jobs are dispatched after order creation
    // ---------------------------------------------------------------

    public function test_order_creation_dispatches_queue_jobs(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'Queue Test',
            'customer_email' => 'queue@test.com',
            'item'           => 'phone',
            'quantity'       => 1,
            'total_price'    => 799.99,
        ]);

        $response->assertStatus(201);

        // Message Queue: Verify both async jobs were dispatched
        Queue::assertPushed(SendOrderConfirmationJob::class);
        Queue::assertPushed(UpdateInventoryJob::class);
    }
}
