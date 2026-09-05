<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\BlogService;

class BlogController extends Controller
{
    // Footer and navigation data reach every `site.*` view through the
    // StorefrontChromeService view composer, so these actions only build
    // what is specific to the blog.

    public function index()
    {
        $blogService = app(BlogService::class);

        $posts = $blogService->posts();
        $categories = $blogService->categories();
        $featuredPosts = $blogService->featuredPosts();

        return view('site.pages.blog.index', compact(
            'posts',
            'categories',
            'featuredPosts'
        ));
    }

    public function show(string $slug)
    {
        $post = app(BlogService::class)->findPostBySlug($slug);

        return view('site.pages.blog.show', compact('post'));
    }

    public function category(string $slug)
    {
        $blogService = app(BlogService::class);

        $category = $blogService->findCategoryBySlug($slug);
        $posts = $blogService->postsByCategory($category);
        $categories = $blogService->categories();

        return view('site.pages.blog.category', compact(
            'category',
            'posts',
            'categories'
        ));
    }
}
