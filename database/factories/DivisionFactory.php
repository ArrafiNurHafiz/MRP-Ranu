<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Access\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'code' => Str::lower(fake()->unique()->lexify('div???')),
            'name' => fake()->word(),
            'is_active' => true,
        ];
    }
}
