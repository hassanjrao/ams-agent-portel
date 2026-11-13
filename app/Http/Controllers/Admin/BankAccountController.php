<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/bank-accounts/Index', [
            'accounts' => BankAccount::query()->orderBy('sort_order')->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BankAccount::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bank account added.']);

        return back();
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bank account updated.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_title' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'max:40'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Za-z]{2}\d{2}[A-Za-z0-9 ]+$/'],
            'branch' => ['nullable', 'string', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ], ['iban.regex' => 'An IBAN starts with the country and check digits, e.g. PK36SCBL0000001123456702.']);

        if (isset($data['iban'])) {
            $data['iban'] = strtoupper(str_replace(' ', '', $data['iban']));
        }
        $data['sort_order'] ??= 0;

        return $data;
    }
}
