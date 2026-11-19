<?php

namespace App\Presenters;

use App\Models\FlightGroup;
use App\Models\FlightGroupLeg;

/**
 * A group as agencies see it: route, flights, seats left and fares. Never the supplier PNR.
 * Load airline, groupType and legs, and use withSeatsTaken(), before presenting.
 */
class GroupPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(FlightGroup $group): array
    {
        return [
            'id' => $group->id,
            'sector' => $group->sector,
            'group_type' => $group->groupType->name,
            'airline' => $group->airline->only('name', 'code'),
            'departure_date' => $group->departure_date->toDateString(),
            'return_date' => $group->return_date?->toDateString(),
            'duration_days' => $group->duration_days,
            'total_seats' => $group->total_seats,
            'seats_available' => $group->seatsAvailable(),
            'adult_fare' => $group->adult_fare,
            'child_fare' => $group->childFare(),
            // Null when the group doesn't take infants.
            'infant_fare' => $group->infant_fare,
            'hold_minutes' => $group->holdMinutes(),
            'notes' => $group->notes,
            'bookable' => $group->isBookable(),
            'legs' => $group->legs->map(fn (FlightGroupLeg $leg) => self::leg($leg))->all(),
        ];
    }

    /**
     * Times are the airports' local times as AMS typed them, so they go out as plain strings:
     * converting them through a timezone would shift them.
     *
     * @return array<string, mixed>
     */
    public static function leg(FlightGroupLeg $leg): array
    {
        return [
            'flight_number' => $leg->flight_number,
            'origin' => $leg->origin,
            'destination' => $leg->destination,
            'departs_on' => $leg->departs_at->toDateString(),
            'departs_time' => $leg->departs_at->format('H:i'),
            'arrives_on' => $leg->arrives_at->toDateString(),
            'arrives_time' => $leg->arrives_at->format('H:i'),
            'baggage' => $leg->baggage,
            'meal' => $leg->meal,
        ];
    }
}
