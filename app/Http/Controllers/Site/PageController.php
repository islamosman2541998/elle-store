<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::query()
            ->active()
            ->with('activeImages')
            ->where(function ($query) use ($slug) {
                $query->where('slug_ar', $slug)
                    ->orWhere('slug_en', $slug);
            })
            ->firstOrFail();

        // Footer and navigation come from the `site.*` view composer.
        return view('site.pages.page-show', compact('page'));
    }
}
