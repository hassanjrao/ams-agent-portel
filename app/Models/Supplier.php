<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Who AMS buys outsourced group seats from. Only AMS staff see suppliers.
 *
 * @property int $id
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $city
 * @property string|null $notes
 * @property bool $is_active
 */
#[Fillable(['name', 'contact_person', 'phone', 'email', 'city', 'notes', 'is_active'])]
class Supplier extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'flight_groups_count' => 'integer',
        ];
    }

    /** @return HasMany<FlightGroup, $this> */
    public function flightGroups(): HasMany
    {
        return $this->hasMany(FlightGroup::class);
    }
}
