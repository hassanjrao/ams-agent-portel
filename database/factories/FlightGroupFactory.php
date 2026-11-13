<?php

namespace Database\Factories;

use App\Enums\GroupStatus;
use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlightGroup>
 */
class FlightGroupFactory extends Factory
{
    public function definition(): array
    {
        $departure = now()->addDays(fake()->numberBetween(10, 60))->startOfDay();

        return [
            'group_type_id' => GroupType::factory(),
            'airline_id' => Airline::factory(),
            'sector' => 'MUX-JED-MUX',
            'departure_date' => $departure,
            'return_date' => $departure->copy()->addDays(21),
            'duration_days' => 21,
            'total_seats' => 10,
            'adult_fare' => 169000,
            'child_fare' => 150000,
            'infant_fare' => 40000,
            'status' => GroupStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => GroupStatus::Draft]);
    }
}
