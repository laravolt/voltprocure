<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
final class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('SUP-####'),
            'name' => 'PT '.fake()->lastName().' Sentosa',
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'city' => 'Bandung',
            'payment_terms' => '30 hari',
            'is_active' => true,
        ];
    }
}
