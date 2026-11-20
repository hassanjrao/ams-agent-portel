<?php

namespace App\Http\Requests\Admin;

use App\Enums\GroupSourcing;
use App\Enums\GroupStatus;
use App\Models\FlightGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FlightGroupRequest extends FormRequest
{
    // A group saved without saying where its seats came from was bought by AMS directly.
    protected function prepareForValidation(): void
    {
        if (! $this->filled('sourcing')) {
            $this->merge(['sourcing' => GroupSourcing::Direct->value]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_type_id' => ['required', 'integer', Rule::exists('group_types', 'id')],
            'airline_id' => ['required', 'integer', Rule::exists('airlines', 'id')],
            // Airport codes joined by dashes: MUX-JED-MUX, LHE-MCT-JED.
            'sector' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z]{3}(-[A-Za-z]{3})+$/'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:120'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:999'],
            'adult_fare' => ['required', 'integer', 'min:0', 'max:10000000'],
            // Empty child fare: children pay the adult fare. Empty infant fare: no infants.
            'child_fare' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'infant_fare' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            // Flat PKR off every booking on the group, for every agency.
            'discount_per_booking' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'supplier_pnr' => ['nullable', 'string', 'max:20'],
            'sourcing' => ['required', Rule::enum(GroupSourcing::class)],
            'supplier_id' => ['nullable', 'required_if:sourcing,'.GroupSourcing::Outsourced->value, 'integer', Rule::exists('suppliers', 'id')],
            'hold_minutes' => ['nullable', 'integer', 'min:'.FlightGroup::MIN_HOLD_MINUTES, 'max:'.FlightGroup::MAX_HOLD_MINUTES],
            'status' => ['required', Rule::enum(GroupStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'legs' => ['required', 'array', 'min:1', 'max:8'],
            'legs.*.flight_number' => ['required', 'string', 'max:10'],
            'legs.*.origin' => ['required', 'string', 'size:3', 'alpha'],
            'legs.*.destination' => ['required', 'string', 'size:3', 'alpha', 'different:legs.*.origin'],
            // A new group can't depart in the past: a mistyped year or day/month swap would
            // otherwise save it and hide it from the list. Two years ahead is plenty for a block.
            'legs.*.departs_at' => ['required', 'date', $this->isMethod('post') ? 'after_or_equal:today' : 'after:2020-01-01', 'before:+2 years'],
            'legs.*.arrives_at' => ['required', 'date', 'after:2020-01-01', 'before:+2 years'],
            'legs.*.baggage' => ['nullable', 'string', 'max:30'],
            'legs.*.meal' => ['boolean'],
        ];
    }

    /**
     * A flight can't land on a day before it took off. Only the dates are compared: in local time a
     * short westbound flight can land at an earlier clock time than it left.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('legs', []) as $i => $leg) {
                $departs = $leg['departs_at'] ?? null;
                $arrives = $leg['arrives_at'] ?? null;
                if (! is_string($departs) || ! is_string($arrives) || strtotime($departs) === false || strtotime($arrives) === false) {
                    continue;
                }
                if (Carbon::parse($arrives)->startOfDay()->lt(Carbon::parse($departs)->startOfDay())) {
                    $validator->errors()->add("legs.{$i}.arrives_at", "The arrival date can't be before the departure date.");
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sector.regex' => 'Write the route as airport codes joined by dashes, e.g. MUX-JED-MUX.',
            'legs.required' => 'Add at least one flight.',
            'legs.*.departs_at.after_or_equal' => 'This date has already passed. Check the day, month and year.',
            'legs.*.departs_at.after' => 'Check the day, month and year of this flight.',
            'legs.*.departs_at.before' => 'This date is more than two years away. Check the year.',
            'legs.*.arrives_at.after' => 'Check the day, month and year of this flight.',
            'legs.*.arrives_at.before' => 'This date is more than two years away. Check the year.',
            'supplier_id.required_if' => 'Choose the supplier this group was bought from.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group_type_id' => 'category',
            'airline_id' => 'airline',
            'sector' => 'route',
            'duration_days' => 'days',
            'supplier_pnr' => 'airline PNR',
            'supplier_id' => 'supplier',
            'discount_per_booking' => 'discount',
            'hold_minutes' => 'payment window',
            'legs.*.flight_number' => 'flight number',
            'legs.*.origin' => 'from',
            'legs.*.destination' => 'to',
            'legs.*.departs_at' => 'departure time',
            'legs.*.arrives_at' => 'arrival time',
            'legs.*.baggage' => 'baggage',
        ];
    }

    /**
     * Group fields only: the flights are passed to the service separately.
     *
     * @return array<string, mixed>
     */
    public function groupAttributes(): array
    {
        $attributes = collect($this->validated())->except('legs')->all();
        $attributes['discount_per_booking'] = (int) ($attributes['discount_per_booking'] ?? 0);
        // A direct group has no supplier, whatever the form still held.
        if ($attributes['sourcing'] === GroupSourcing::Direct->value) {
            $attributes['supplier_id'] = null;
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function legs(): array
    {
        return array_values($this->validated('legs'));
    }
}
