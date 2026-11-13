<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $flight_group_id
 * @property int $sort_order
 * @property string $flight_number
 * @property string $origin
 * @property string $destination
 * @property Carbon $departs_at
 * @property Carbon $arrives_at
 * @property string|null $baggage
 * @property bool $meal
 */
#[Fillable(['sort_order', 'flight_number', 'origin', 'destination', 'departs_at', 'arrives_at', 'baggage', 'meal'])]
class FlightGroupLeg extends Model
{
    protected function casts(): array
    {
        return [
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
            'meal' => 'boolean',
        ];
    }

    /** @return BelongsTo<FlightGroup, $this> */
    public function flightGroup(): BelongsTo
    {
        return $this->belongsTo(FlightGroup::class);
    }
}
