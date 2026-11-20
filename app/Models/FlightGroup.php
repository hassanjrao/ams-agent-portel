<?php

namespace App\Models;

use App\Enums\GroupSourcing;
use App\Enums\GroupStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_type_id
 * @property int $airline_id
 * @property string $sector
 * @property Carbon $departure_date
 * @property Carbon|null $return_date
 * @property int|null $duration_days
 * @property int $total_seats
 * @property int $adult_fare
 * @property int|null $child_fare Empty: children pay the adult fare.
 * @property int|null $infant_fare Empty: the group doesn't take infants.
 * @property int $discount_per_booking Flat amount taken off every booking on the group.
 * @property string|null $supplier_pnr
 * @property GroupSourcing $sourcing Admin-only: bought by AMS directly or outsourced.
 * @property int|null $supplier_id Set only when outsourced.
 * @property int|null $hold_minutes
 * @property GroupStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $deleted_at Deleted from the admin; the row stays for past bookings.
 * @property int|null $seats_taken Only when loaded through withSeatsTaken().
 */
#[Fillable([
    'group_type_id', 'airline_id', 'sector', 'departure_date', 'return_date', 'duration_days',
    'total_seats', 'adult_fare', 'child_fare', 'infant_fare', 'discount_per_booking', 'supplier_pnr', 'sourcing', 'supplier_id', 'hold_minutes', 'status', 'notes',
])]
class FlightGroup extends Model
{
    use HasFactory, SoftDeletes;

    // Bounds of the payment window an admin can set: five minutes to two days.
    public const MIN_HOLD_MINUTES = 5;

    public const MAX_HOLD_MINUTES = 2880;

    protected function casts(): array
    {
        return [
            'status' => GroupStatus::class,
            'sourcing' => GroupSourcing::class,
            'departure_date' => 'date',
            'return_date' => 'date',
        ];
    }

    /** @return BelongsTo<GroupType, $this> */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(GroupType::class);
    }

    /** @return BelongsTo<Airline, $this> */
    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<FlightGroupLeg, $this> */
    public function legs(): HasMany
    {
        return $this->hasMany(FlightGroupLeg::class)->orderBy('sort_order');
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Adds seats_taken: seats held by confirmed bookings and by holds that haven't run out yet.
     * An expired hold stops counting the moment it expires, before any job marks it expired.
     *
     * @param  Builder<FlightGroup>  $query
     */
    public function scopeWithSeatsTaken(Builder $query): void
    {
        $query->withSum(['bookings as seats_taken' => fn (Builder $q) => $q->holdingSeats()], 'seats');
    }

    /**
     * Groups an agent may book: published and not yet departed.
     *
     * @param  Builder<FlightGroup>  $query
     */
    public function scopeBookable(Builder $query): void
    {
        $query->where('status', GroupStatus::Published)->whereDate('departure_date', '>=', today());
    }

    public function seatsAvailable(): int
    {
        $taken = $this->seats_taken ?? $this->bookings()->holdingSeats()->sum('seats');

        return max(0, $this->total_seats - (int) $taken);
    }

    // What a child pays: the child fare, or the adult fare when the group has none.
    public function childFare(): int
    {
        return $this->child_fare ?? $this->adult_fare;
    }

    // Infants can only be booked on a group that has an infant fare.
    public function takesInfants(): bool
    {
        return $this->infant_fare !== null;
    }

    public function holdMinutes(): int
    {
        return $this->hold_minutes ?? config('portal.hold_minutes');
    }

    public function isBookable(): bool
    {
        return $this->status === GroupStatus::Published && $this->departure_date->gte(today());
    }
}
