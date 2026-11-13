<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $logo_path
 * @property bool $is_active
 */
#[Fillable(['name', 'code', 'logo_path', 'is_active'])]
class Airline extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<FlightGroup, $this> */
    public function flightGroups(): HasMany
    {
        return $this->hasMany(FlightGroup::class);
    }
}
