<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'paid_on' => ['required', 'date', 'before_or_equal:today', 'after:'.now()->subYear()->toDateString()],
            // Optional: the booking this payment is for. Only the agency's own bookings.
            'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')->where('agency_id', $this->user()->agency_id)],
            'description' => ['nullable', 'string', 'max:255'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt.mimes' => 'Upload the receipt as a photo (JPG, PNG) or a PDF.',
            'receipt.max' => 'The receipt must be smaller than 5 MB.',
            'paid_on.before_or_equal' => 'The payment date cannot be in the future.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bank_account_id' => 'bank account',
            'paid_on' => 'payment date',
            'booking_id' => 'booking',
        ];
    }
}
