<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FlightGroupService
{
    /**
     * Create or update a group together with its flights. The flights are replaced as a set, and the
     * group's departure/return dates follow its first and last flight.
     *
     * @param  array<string, mixed>  $attributes  validated group fields (FlightGroupRequest)
     * @param  list<array<string, mixed>>  $legs  validated flights, in any order
     */
    public function save(FlightGroup $group, array $attributes, array $legs, User $by): FlightGroup
    {
        return DB::transaction(function () use ($group, $attributes, $legs, $by) {
            if ($group->exists) {
                // Same lock bookings take, so no hold can land between this check and the save.
                $locked = FlightGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
                $taken = $locked->total_seats - $locked->seatsAvailable();
                if ($attributes['total_seats'] < $taken) {
                    throw ValidationException::withMessages([
                        'total_seats' => "{$taken} seats are already booked or on hold, so the group can't have fewer.",
                    ]);
                }
            }

            usort($legs, fn (array $a, array $b) => strcmp($a['departs_at'], $b['departs_at']));

            $group->fill($attributes);
            $group->sector = strtoupper($attributes['sector']);
            $group->departure_date = Carbon::parse($legs[0]['departs_at'])->toDateString();
            $group->return_date = count($legs) > 1 ? Carbon::parse(end($legs)['departs_at'])->toDateString() : null;
            $group->created_by ??= $by->id;
            $group->save();

            $group->legs()->delete();
            foreach (array_values($legs) as $i => $leg) {
                $group->legs()->create([
                    ...$leg,
                    'flight_number' => strtoupper(str_replace(' ', '', $leg['flight_number'])),
                    'origin' => strtoupper($leg['origin']),
                    'destination' => strtoupper($leg['destination']),
                    'sort_order' => $i,
                ]);
            }

            return $group->load('legs');
        });
    }

    /**
     * Delete a group from the portal. It is a soft delete: agencies and the admin lists stop showing
     * it, while the row stays so bookings already made on it keep their flights. A group still
     * holding seats (confirmed bookings, or holds that haven't run out) can't be deleted.
     *
     * @throws ValidationException when bookings still hold seats on it
     */
    public function delete(FlightGroup $group): void
    {
        DB::transaction(function () use ($group) {
            // Same lock bookings take, so no hold can land while this checks.
            $locked = FlightGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();

            $live = Booking::query()->where('flight_group_id', $locked->id)->holdingSeats()->count();
            if ($live > 0) {
                throw ValidationException::withMessages([
                    'group' => $live === 1
                        ? 'One booking is confirmed or on hold on this group. Cancel it first, then delete the group.'
                        : "{$live} bookings are confirmed or on hold on this group. Cancel them first, then delete the group.",
                ]);
            }

            $locked->delete();
        });
    }
}
