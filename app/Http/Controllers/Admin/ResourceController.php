<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function index(PricingService $pricing)
    {
        $resources = Resource::orderByRaw("FIELD(slug,'zahira-green-ground') DESC")
            ->orderBy('name')
            ->get();

        // Attach resolved pricing (config defaults merged with DB overrides) per resource.
        $resources->each(function ($resource) use ($pricing) {
            $resource->resolved_pricing = collect($pricing->slots($resource))
                ->map(function ($slot, $key) use ($resource, $pricing) {
                    return [
                        'slot_type' => $key,
                        'label'     => $slot['label'],
                        'type'      => $slot['type'],
                        'rate'      => $pricing->unitPrice($resource, $key, 1),
                        'base_rate' => $slot['rate'],
                    ];
                })->values();
        });

        return Inertia::render('Admin/Resources/Index', [
            'resources' => $resources,
        ]);
    }

    public function update(Request $request, Resource $resource)
    {
        $request->validate([
            'pricing'            => ['required', 'array'],
            'pricing.*.slot_type'=> ['required', 'string'],
            'pricing.*.rate'     => ['required', 'integer', 'min:0'],
            'is_active'          => ['boolean'],
        ]);

        $overrides = collect($request->input('pricing'))
            ->keyBy('slot_type')
            ->map(fn ($item) => (int) $item['rate'])
            ->all();

        $resource->update([
            'pricing_overrides' => $overrides,
            'is_active'         => $request->boolean('is_active', $resource->is_active),
        ]);

        return back()->with('success', 'Pricing updated.');
    }
}
