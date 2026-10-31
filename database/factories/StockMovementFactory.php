<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'quantity' => 5,
            'type' => StockMovementType::Adjustment,
            'source_type' => (new Item)->getMorphClass(),
            'source_id' => fn (array $attributes): mixed => $attributes['item_id'],
            'created_by' => User::factory(),
            'moved_at' => now(),
        ];
    }
}
