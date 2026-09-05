<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocialLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'platform',
        'label_ar',
        'label_en',
        'url',
        'icon',
        'is_active',
        'open_in_new_tab',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getLabelAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->label_ar ?: ucfirst($this->platform))
            : ($this->label_en ?: ucfirst($this->platform));
    }

    public static function platforms(): array
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'youtube' => 'YouTube',
            'x' => 'X / Twitter',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'pinterest' => 'Pinterest',
        ];
    }
}