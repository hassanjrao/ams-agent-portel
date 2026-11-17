<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The date range of an agency statement. Defaults to the last three months.
 */
class StatementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        $to = $this->validated('to') ? CarbonImmutable::parse($this->validated('to')) : CarbonImmutable::today();
        $from = $this->validated('from')
            ? CarbonImmutable::parse($this->validated('from'))
            : $to->subMonthsNoOverflow(3)->startOfMonth();

        return [$from, $to];
    }
}
