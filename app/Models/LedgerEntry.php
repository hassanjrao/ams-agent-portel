<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Written only by LedgerService and never updated: a correction is a new adjustment entry.
 *
 * @property int $id
 * @property int $agency_id
 * @property string $voucher_number
 * @property Carbon $entry_date
 * @property LedgerEntryType $type
 * @property string $description
 * @property int $debit
 * @property int $credit
 * @property int|null $booking_id
 * @property int|null $payment_id
 * @property int|null $created_by
 */
class LedgerEntry extends Model
{
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'entry_date' => 'date',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
