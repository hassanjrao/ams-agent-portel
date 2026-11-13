<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GroupTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/group-types/Index', [
            'groupTypes' => GroupType::query()->withCount('flightGroups')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        GroupType::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category added.']);

        return back();
    }

    public function update(Request $request, GroupType $groupType): RedirectResponse
    {
        $groupType->update($this->validated($request, $groupType));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?GroupType $groupType = null): array
    {
        $request->merge(['slug' => Str::slug((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', Rule::unique('group_types', 'slug')->ignore($groupType)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ], ['slug.unique' => 'A category with this name already exists.']);
    }
}
