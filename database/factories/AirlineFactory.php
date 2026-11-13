<?php

namespace Database\Factories;

use App\Models\Airline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Airline>
 */
class AirlineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Airways',
            'code' => fake()->unique()->bothify('?#'),
            'is_active' => true,
        ];
    }
}
