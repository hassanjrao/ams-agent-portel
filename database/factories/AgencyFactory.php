<?php

namespace Database\Factories;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Travel & Tours',
            'contact_person' => fake()->name(),
            'phone' => '0300'.fake()->numerify('#######'),
            'email' => fake()->unique()->companyEmail(),
            'city' => fake()->randomElement(['Multan', 'Lahore', 'Khanewal', 'Karachi']),
            'country' => 'Pakistan',
            'status' => AgencyStatus::Active,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => AgencyStatus::Pending, 'approved_at' => null]);
    }
}
