<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PassengerType;
use Illuminate\Validation\Rule;

/**
 * Passenger names as airlines take them: Latin letters only, as printed in the passport.
 */
trait PassengerRules
{
    public const TITLES = ['MR', 'MRS', 'MS', 'MSTR', 'MISS'];

    /**
     * @return array<string, mixed>
     */
    protected function passengerRules(bool $withTickets = false): array
    {
        $name = ['required', 'string', 'max:60', "regex:/^[A-Za-z][A-Za-z '\\-]*$/"];

        return [
            'passengers' => ['required', 'array', 'min:1', 'max:200'],
            'passengers.*.type' => ['required', Rule::enum(PassengerType::class)],
            'passengers.*.title' => ['required', Rule::in(self::TITLES)],
            'passengers.*.given_name' => $name,
            'passengers.*.surname' => $name,
            'passengers.*.passport_number' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'passengers.*.nationality' => ['nullable', 'string', 'max:60'],
            'passengers.*.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'passengers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            ...($withTickets ? ['passengers.*.ticket_number' => ['nullable', 'string', 'max:30']] : []),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function passengerMessages(): array
    {
        return [
            'passengers.*.given_name.regex' => 'Use English letters only, as in the passport.',
            'passengers.*.surname.regex' => 'Use English letters only, as in the passport.',
            'passengers.*.passport_number.alpha_num' => 'Letters and digits only, without spaces.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function passengerAttributes(): array
    {
        return [
            'passengers.*.title' => 'title',
            'passengers.*.given_name' => 'given name',
            'passengers.*.surname' => 'surname',
            'passengers.*.passport_number' => 'passport number',
            'passengers.*.date_of_birth' => 'date of birth',
            'passengers.*.passport_expiry' => 'passport expiry',
            'passengers.*.ticket_number' => 'ticket number',
        ];
    }
}
