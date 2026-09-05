<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A carrier's tracking URL template, e.g.
 * https://bosta.co/tracking-shipments?tracking_number={tracking_number}
 *
 * Without the {tracking_number} placeholder the store still builds a link, but
 * it points at the carrier's generic page instead of the customer's shipment -
 * a dead end that nobody notices until a customer complains. Catch it here.
 */
class TrackingUrlTemplate implements ValidationRule
{
    public const PLACEHOLDER = '{tracking_number}';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $value = trim((string) $value);

        if (! preg_match('#^https?://#i', $value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail(__('admin.tracking_url_template_invalid'));

            return;
        }

        if (! str_contains($value, self::PLACEHOLDER)) {
            $fail(__('admin.tracking_url_template_missing_placeholder'));
        }
    }
}
