<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\AgencyService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Sign-up is for travel agencies: it creates the agency (waiting for AMS approval) and its first login.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private AgencyService $agencies) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $data = Validator::make($input, [
            'agency_name' => ['required', 'string', 'max:150'],
            ...$this->profileRules(),
            // Pakistani mobile or landline, with or without +92 / spaces / dashes.
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 \-]{6,20}$/'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => 'Enter a phone number with digits only, e.g. 0300 1234567.',
        ], [
            'agency_name' => 'agency name',
            'name' => 'your name',
        ])->validate();

        return $this->agencies->register($data);
    }
}
