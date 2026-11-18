<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/suppliers/Index', [
            'suppliers' => Supplier::query()->withCount('flightGroups')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier added.']);

        return back();
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier updated.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], ['phone.regex' => 'Use digits only, e.g. 0300 1234567.']);
    }
}
