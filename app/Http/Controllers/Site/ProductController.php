<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(string $slug)
{
    return view('site.pages.products.show', [
        'slug' => $slug,
    ]);
}
}