<?php

namespace App\Services;

use App\Models\NavigationLink;

class NavigationService
{
    public function linksByLocation(string $location)
    {
        return NavigationLink::query()
            ->with([
                'page',
                'category.transNow',
                'brand.transNow',
            ])
            ->where('location', $location)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function headerLinks()
    {
        return $this->linksByLocation('header');
    }

    public function mobileLinks()
    {
        return $this->linksByLocation('mobile');
    }

    public function footerLinks()
    {
        return $this->linksByLocation('footer');
    }

    public function navigationData(): array
    {
        return [
            'header_links' => $this->headerLinks(),
            'mobile_links' => $this->mobileLinks(),
            'footer_links' => $this->footerLinks(),
        ];
    }
}