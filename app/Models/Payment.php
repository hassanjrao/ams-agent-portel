<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $agency_id
 * @property int $user_id
 * @property int|null $booking_id
 * @property int $bank_account_id
 * @property int $amount
 * @property Carbon $paid_on
 * @property string|null $description
 * @property string $receipt_path
 * @property PaymentStatus $status
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property string|null $admin_remarks
 */
#[Fillable(['booking_id', 'bank_account_id', 'amount', 'paid_on', 'description', 'receipt_path'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_on' => 'date',
            'reviewed_at' => 'datetime',
            'agency_id' => 'integer',
            'user_id' => 'integer',
            'booking_id' => 'integer',
            'bank_account_id' => 'integer',
            'amount' => 'integer',
            'reviewed_by' => 'integer',
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

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
