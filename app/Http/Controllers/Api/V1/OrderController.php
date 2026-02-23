<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API Design: Thin controller — all business logic lives in services.
 *
 * This controller only handles HTTP concerns: receiving requests,
 * calling the appropriate service method, and returning responses.
 * No DB queries, no business logic, no direct model access.
 *
 * SOA: OrderService is injected via constructor injection.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * POST /api/v1/orders — Create a new order.
     *
     * API Design: Uses FormRequest for validation (422 on failure),
     * returns 201 with the created resource on success.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (\RuntimeException $e) {
            // SOA: InventoryService threw — item out of stock
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/orders — List orders with pagination.
     *
     * API Design: Supports ?limit= query parameter for page size.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('limit', 10);
        $orders = $this->orderService->listOrders($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * GET /api/v1/orders/{id} — Get a single order.
     *
     * API Design: Returns 404 if order not found (via findOrFail).
     */
    public function show(int $id): OrderResource
    {
        return new OrderResource($this->orderService->findOrder($id));
    }

    /**
     * PATCH /api/v1/orders/{id} — Update order status.
     *
     * API Design: Only updates the status field (pending/confirmed/failed).
     */
    public function update(Request $request, int $id): OrderResource
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,failed'],
        ]);

        $order = $this->orderService->updateOrderStatus($id, $request->input('status'));

        return new OrderResource($order);
    }
}
