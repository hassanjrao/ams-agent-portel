<?php

namespace App\Http\Requests\Agent;

use App\Http\Requests\Concerns\PassengerRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    use PassengerRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'adults' => ['required', 'integer', 'min:1', 'max:99'],
            'children' => ['required', 'integer', 'min:0', 'max:99'],
            'infants' => ['required', 'integer', 'min:0', 'max:99'],
            'remarks' => ['nullable', 'string', 'max:500'],
            ...$this->passengerRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->passengerMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->passengerAttributes();
    }
}
