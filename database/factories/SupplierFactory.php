<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Travels',
            'contact_person' => fake()->name(),
            'phone' => '0300'.fake()->numerify('#######'),
            'city' => 'Lahore',
            'is_active' => true,
        ];
    }
}
