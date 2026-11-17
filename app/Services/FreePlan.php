<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Support\Collection;

/**
 * The portal's free plan: the first agencies to sign up (config portal.free_agency_limit) use it
 * fully. Agencies after them can log in and look around, but every action is refused until the
 * limit is raised or lifted. Order is by sign-up (agency id), so an agency never loses access
 * because someone else signed up later.
 */
class FreePlan
{
    public static function limit(): ?int
    {
        $limit = (int) config('portal.free_agency_limit');

        return $limit > 0 ? $limit : null;
    }

    /**
     * Whether this agency is one of the first N to sign up, and so may act on the portal.
     */
    public static function covers(Agency $agency): bool
    {
        $limit = self::limit();

        return $limit === null
            || Agency::query()->where('id', '<', $agency->id)->count() < $limit;
    }

    /**
     * Ids of the agencies the plan covers, for marking the others in lists.
     *
     * @return Collection<int, int>
     */
    public static function coveredIds(): Collection
    {
        $limit = self::limit();

        return $limit === null
            ? Agency::query()->pluck('id')
            : Agency::query()->orderBy('id')->limit($limit)->pluck('id');
    }

    /**
     * For AMS staff: the limit, how many agencies signed up and how many of them are locked out.
     *
     * @return array{limit: int, agencies: int, locked: int, reached: bool, contact: array{name: string, whatsapp: string}}|null
     */
    public static function summary(): ?array
    {
        $limit = self::limit();
        if ($limit === null) {
            return null;
        }

        $agencies = Agency::query()->count();

        return [
            'limit' => $limit,
            'agencies' => $agencies,
            'locked' => max(0, $agencies - $limit),
            // Reached once an agency has signed up beyond the free ones.
            'reached' => $agencies > $limit,
            'contact' => config('portal.upgrade_contact'),
        ];
    }

    public static function lockedMessage(): string
    {
        return 'Your agency can view the portal but cannot book, pay or change bookings yet: the portal\'s free plan covers its first '
            .self::limit().' agencies. Please contact AMS Global Travel & Tours.';
    }
}
