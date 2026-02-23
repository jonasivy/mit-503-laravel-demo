<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * SOA: InventoryService — Single responsibility for inventory operations.
 *
 * This service handles stock availability checks and item reservation.
 * It is called SYNCHRONOUSLY by OrderService before an order is persisted,
 * ensuring we never accept an order for out-of-stock items.
 */
class InventoryService
{
    /**
     * Simulated inventory with item names and available quantities.
     * In production, this would query an inventory database or external service.
     */
    private array $stock = [
        'laptop'    => 50,
        'phone'     => 100,
        'tablet'    => 30,
        'monitor'   => 25,
        'keyboard'  => 200,
        'mouse'     => 200,
        'headset'   => 75,
    ];

    /**
     * Check if the requested item is in stock with sufficient quantity.
     *
     * SOA: This is a synchronous check — OrderService calls this BEFORE
     * saving the order. If the item is unavailable, the order is rejected
     * immediately with a 422 response.
     *
     * @param string $item     The item name to check
     * @param int    $quantity The requested quantity
     * @return bool True if item is available in sufficient quantity
     */
    public function checkAvailability(string $item, int $quantity): bool
    {
        $itemKey = strtolower($item);
        $available = $this->stock[$itemKey] ?? 0;

        Log::info("InventoryService: Checking stock for '{$item}' — requested: {$quantity}, available: {$available}");

        return $available >= $quantity;
    }

    /**
     * Reserve (deduct) the requested quantity from simulated stock.
     *
     * In production, this would update the inventory database within a
     * database transaction to prevent race conditions.
     *
     * @param string $item     The item to reserve
     * @param int    $quantity The quantity to deduct
     * @return array{item: string, quantity_deducted: int, remaining: int} Reservation details
     */
    public function reserve(string $item, int $quantity): array
    {
        $itemKey = strtolower($item);
        $before = $this->stock[$itemKey] ?? 0;
        $this->stock[$itemKey] = max(0, $before - $quantity);

        Log::info("InventoryService: Reserved {$quantity}x '{$item}' — stock: {$before} → {$this->stock[$itemKey]}");

        return [
            'item' => $item,
            'quantity_deducted' => $quantity,
            'remaining' => $this->stock[$itemKey],
        ];
    }
}
