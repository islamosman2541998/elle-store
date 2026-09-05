<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Response;

/**
 * sitemap.xml and robots.txt.
 *
 * Both are generated rather than stored as files, so a product added in the
 * dashboard shows up without anyone regenerating anything.
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect()
            ->push(['loc' => route('site.home'), 'priority' => '1.0', 'freq' => 'daily'])
            ->push(['loc' => route('site.shop'), 'priority' => '0.9', 'freq' => 'daily']);

        // The slug lives on the translation, one row per locale.
        Product::query()
            ->where('is_active', true)
            ->with('translations:id,product_id,locale,slug')
            ->select('id', 'updated_at')
            ->chunk(500, function ($products) use ($urls) {
                foreach ($products as $product) {
                    $slug = $product->translations->firstWhere('locale', app()->getLocale())?->slug
                        ?? $product->translations->first()?->slug;

                    if (! $slug) {
                        continue;
                    }

                    $urls->push([
                        'loc' => route('site.products.show', ['slug' => $slug]),
                        'lastmod' => $product->updated_at?->toAtomString(),
                        'priority' => '0.8',
                        'freq' => 'weekly',
                    ]);
                }
            });

        foreach (Page::query()->where('is_active', true)->get() as $page) {
            $urls->push([
                'loc' => route('site.pages.show', ['slug' => $page->slug_ar ?: $page->slug_en]),
                'lastmod' => $page->updated_at?->toAtomString(),
                'priority' => '0.5',
                'freq' => 'monthly',
            ]);
        }

        // Blog and category pages only if the store actually routes to them.
        if (app('router')->has('site.blog.show')) {
            foreach (BlogPost::query()->where('is_active', true)->get() as $post) {
                $urls->push([
                    'loc' => route('site.blog.show', ['slug' => $post->slug_ar ?: $post->slug_en]),
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => '0.6',
                    'freq' => 'weekly',
                ]);
            }
        }

        if (app('router')->has('site.categories.show')) {
            foreach (Category::query()->where('is_active', true)->with('translations')->get() as $category) {
                $slug = $category->translations->firstWhere('locale', app()->getLocale())?->slug
                    ?? $category->translations->first()?->slug;

                if ($slug) {
                    $urls->push([
                        'loc' => route('site.categories.show', ['slug' => $slug]),
                        'priority' => '0.7',
                        'freq' => 'weekly',
                    ]);
                }
            }
        }

        $xml = view('site.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $settings = StoreSetting::current();

        // A store set to "noindex" must not invite crawlers in through robots.
        $blocked = str_contains((string) $settings->robots, 'noindex');

        $lines = ['User-agent: *'];

        if ($blocked) {
            $lines[] = 'Disallow: /';
        } else {
            // Anything private, or with no value in an index.
            foreach ([
                '/admin', '/checkout', '/cart', '/my-orders', '/my-account',
                '/account', '/wishlist', '/order/', '/livewire',
            ] as $path) {
                $lines[] = 'Disallow: ' . $path;
            }

            $lines[] = 'Allow: /';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
