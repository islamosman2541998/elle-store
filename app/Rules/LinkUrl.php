<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts either a full external URL or an internal, root-relative path.
 *
 * Laravel's built-in `url` rule rejects paths like `/shop`, which is what
 * internal links in this store actually store. Using `url` on those fields
 * made the form fail validation on a value the admin never typed, and because
 * the field can sit on a hidden tab the save button simply looked dead.
 */
class LinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $value = trim((string) $value);

        // Internal link: /shop, /shop?category=pajamas, /pages/about#section
        if (str_starts_with($value, '/')) {
            // Reject protocol-relative URLs (//evil.com), which look internal
            // but send the visitor off-site.
            if (str_starts_with($value, '//')) {
                $fail(__('validation.url', ['attribute' => $attribute]));
            }

            return;
        }

        // External link: must be a well formed http(s) URL.
        if (filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value)) {
            return;
        }

        $fail(__('validation.url', ['attribute' => $attribute]));
    }
}
