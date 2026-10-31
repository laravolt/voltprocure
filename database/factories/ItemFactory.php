<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
final class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->numerify('ITM-####'),
            'name' => fake()->randomElement(['Toner HP 26A', 'Kertas A4 80 g', 'Keyboard USB', 'Mouse nirkabel', 'Kabel HDMI 2 m']),
            'category' => 'Perlengkapan kantor',
            'unit' => 'pcs',
            'reorder_point' => 5,
            'standard_cost' => 150_000,
            'stock_on_hand' => 0,
            'is_active' => true,
        ];
    }
}
