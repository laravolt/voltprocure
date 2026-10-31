<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
final class DepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->randomElement(['Operasional', 'Teknologi Informasi', 'Keuangan', 'Gudang', 'Umum']),
            'is_active' => true,
        ];
    }
}
