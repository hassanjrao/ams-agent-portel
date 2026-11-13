<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Created and changed only through BookingService, which keeps seats and the ledger consistent.
 *
 * @property int $id
 * @property string $reference
 * @property int $agency_id
 * @property int $user_id
 * @property int $flight_group_id
 * @property int $adults
 * @property int $children
 * @property int $infants
 * @property int $seats
 * @property int $adult_fare
 * @property int $child_fare
 * @property int $infant_fare
 * @property int $total_amount
 * @property BookingStatus $status
 * @property Carbon|null $hold_expires_at
 * @property string|null $pnr
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancel_reason
 * @property string|null $agent_remarks
 */
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<FlightGroup, $this> */
    public function flightGroup(): BelongsTo
    {
        return $this->belongsTo(FlightGroup::class);
    }

    /** @return HasMany<BookingPassenger, $this> */
    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Bookings whose seats are out of the block: confirmed, or on hold and not yet expired.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeHoldingSeats(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', BookingStatus::Confirmed)
            ->orWhere(fn (Builder $q) => $q->where('status', BookingStatus::OnHold)->where('hold_expires_at', '>', now())));
    }

    public function isHoldActive(): bool
    {
        return $this->status === BookingStatus::OnHold && $this->hold_expires_at?->isFuture();
    }

    public function passengerCount(): int
    {
        return $this->adults + $this->children + $this->infants;
    }
}
