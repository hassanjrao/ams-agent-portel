<?php

namespace App\Http\Requests\Admin;

use App\Enums\GroupStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlightGroupRequest extends FormRequest
{
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
            'child_fare' => ['required', 'integer', 'min:0', 'max:10000000'],
            'infant_fare' => ['required', 'integer', 'min:0', 'max:10000000'],
            'supplier_pnr' => ['nullable', 'string', 'max:20'],
            'hold_minutes' => ['nullable', 'integer', 'min:5', 'max:2880'],
            'status' => ['required', Rule::enum(GroupStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'legs' => ['required', 'array', 'min:1', 'max:8'],
            'legs.*.flight_number' => ['required', 'string', 'max:10'],
            'legs.*.origin' => ['required', 'string', 'size:3', 'alpha'],
            'legs.*.destination' => ['required', 'string', 'size:3', 'alpha', 'different:legs.*.origin'],
            'legs.*.departs_at' => ['required', 'date'],
            'legs.*.arrives_at' => ['required', 'date'],
            'legs.*.baggage' => ['nullable', 'string', 'max:30'],
            'legs.*.meal' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sector.regex' => 'Write the route as airport codes joined by dashes, e.g. MUX-JED-MUX.',
            'legs.required' => 'Add at least one flight.',
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
        return collect($this->validated())->except('legs')->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function legs(): array
    {
        return array_values($this->validated('legs'));
    }
}
