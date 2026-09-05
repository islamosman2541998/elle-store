<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\HomepageSettingService;

class HomeController extends Controller
{
    public function index()
    {
        // Store settings, navigation and footer data are supplied to every
        // `site.*` view by StorefrontChromeService via a view composer, so the
        // controller only has to build what is unique to this page.
        $homepage = app(HomepageSettingService::class)->homepageData();

        return view('site.pages.home', compact('homepage'));
    }
}
