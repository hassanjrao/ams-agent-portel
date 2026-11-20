<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $contact_person
 * @property string $phone
 * @property string $email
 * @property string $city
 * @property string $country
 * @property string|null $address
 * @property string|null $logo_path
 * @property AgencyStatus $status
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $admin_notes
 */
#[Fillable(['name', 'contact_person', 'phone', 'email', 'city', 'country', 'address', 'logo_path'])]
class Agency extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AgencyStatus::class,
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
            'bookings_count' => 'integer',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<LedgerEntry, $this> */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function isActive(): bool
    {
        return $this->status === AgencyStatus::Active;
    }

    /**
     * What the agency owes AMS: debits minus credits. Negative means AMS holds credit for them.
     */
    public function balance(): int
    {
        $totals = $this->ledgerEntries()->selectRaw('COALESCE(SUM(debit), 0) AS debit, COALESCE(SUM(credit), 0) AS credit')->first();

        return (int) $totals->debit - (int) $totals->credit;
    }
}
