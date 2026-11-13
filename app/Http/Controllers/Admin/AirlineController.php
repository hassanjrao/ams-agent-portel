<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AirlineController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/airlines/Index', [
            'airlines' => Airline::query()->withCount('flightGroups')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Airline::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Airline added.']);

        return back();
    }

    public function update(Request $request, Airline $airline): RedirectResponse
    {
        $airline->update($this->validated($request, $airline));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Airline updated.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Airline $airline = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'between:2,3', 'alpha_num', Rule::unique('airlines', 'code')->ignore($airline)],
            'is_active' => ['boolean'],
        ]);

        return [...$data, 'code' => strtoupper($data['code'])];
    }
}
