<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogPost;

class BlogService
{
    public function categories()
    {
        return BlogCategory::query()
            ->active()
            ->withCount(['activePosts'])
            ->orderBy('sort_order')
            ->get();
    }

    public function posts(int $perPage = 12)
    {
        return BlogPost::query()
            ->published()
            ->with('category')
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function featuredPosts(int $limit = 6)
    {
        return BlogPost::query()
            ->published()
            ->where('is_featured', true)
            ->with('category')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function postsByCategory(BlogCategory $category, int $perPage = 12)
    {
        return BlogPost::query()
            ->published()
            ->where('blog_category_id', $category->id)
            ->with('category')
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function findPostBySlug(string $slug): BlogPost
    {
        return BlogPost::query()
            ->published()
            ->with('category')
            ->where(function ($query) use ($slug) {
                $query->where('slug_ar', $slug)
                    ->orWhere('slug_en', $slug);
            })
            ->firstOrFail();
    }

    public function findCategoryBySlug(string $slug): BlogCategory
    {
        return BlogCategory::query()
            ->active()
            ->where(function ($query) use ($slug) {
                $query->where('slug_ar', $slug)
                    ->orWhere('slug_en', $slug);
            })
            ->firstOrFail();
    }
}