<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $bank_name
 * @property string $account_title
 * @property string $account_number
 * @property string|null $iban
 * @property string|null $branch
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['bank_name', 'account_title', 'account_number', 'iban', 'branch', 'sort_order', 'is_active'])]
class BankAccount extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
