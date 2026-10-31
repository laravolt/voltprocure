<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequestLine>
 */
final class PurchaseRequestLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'item_id' => Item::factory(),
            'quantity' => 5,
            'estimated_unit_cost' => 150_000,
        ];
    }
}
