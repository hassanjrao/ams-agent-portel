<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PassengerRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing a booking's passenger list. AMS staff may also set the PNR and ticket numbers.
 */
class PassengersRequest extends FormRequest
{
    use PassengerRules;

    protected function prepareForValidation(): void
    {
        $this->dropEmptyPassengers();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $admin = (bool) $this->user()?->isAdmin();

        return [
            ...$this->passengerRules(withTickets: $admin),
            ...($admin ? ['pnr' => ['nullable', 'string', 'max:20', 'alpha_num']] : []),
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
        return [...$this->passengerAttributes(), 'pnr' => 'PNR'];
    }
}
