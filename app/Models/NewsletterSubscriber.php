<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterSubscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'name',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            'subscribed' => __('admin.newsletter_status_subscribed'),
            'unsubscribed' => __('admin.newsletter_status_unsubscribed'),
        ];
    }

    public function subscribe(): void
    {
        $this->update([
            'status' => 'subscribed',
            'subscribed_at' => $this->subscribed_at ?: now(),
            'unsubscribed_at' => null,
        ]);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => $this->unsubscribed_at ?: now(),
        ]);
    }
}