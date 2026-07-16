<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $resources = Resource::query()
            ->active()
            ->orderByRaw("FIELD(slug, 'zahira-green-ground') DESC")
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'location', 'image_path', 'price_per_day']);

        return Inertia::render('Public/Home', [
            'resources' => $resources,
        ]);
    }
}
