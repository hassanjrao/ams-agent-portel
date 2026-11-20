<?php

namespace App\Models;

use App\Enums\PassengerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property PassengerType $type
 * @property string $title
 * @property string $given_name
 * @property string $surname
 * @property string|null $passport_number
 * @property string|null $nationality
 * @property Carbon|null $date_of_birth
 * @property Carbon|null $passport_expiry
 * @property string|null $ticket_number
 */
#[Fillable(['type', 'title', 'given_name', 'surname', 'passport_number', 'nationality', 'date_of_birth', 'passport_expiry'])]
class BookingPassenger extends Model
{
    protected function casts(): array
    {
        return [
            'type' => PassengerType::class,
            'date_of_birth' => 'date',
            'passport_expiry' => 'date',
            'booking_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
