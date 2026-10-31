<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseRequestStatus;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
final class PurchaseRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('PR-2026-####'),
            'department_id' => Department::factory(),
            'requester_id' => User::factory(),
            'purpose' => 'Kebutuhan operasional bulanan',
            'status' => PurchaseRequestStatus::Draft,
        ];
    }
}
