<?php

namespace App\Models;

use App\Services\Notifications\NotificationTemplates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The editable copy of one notification message.
 *
 * Rows are looked up constantly while an order is being placed, so the whole
 * (small) table is memoised per request and flushed whenever one is saved.
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'channel',
        'subject_ar',
        'subject_en',
        'body_ar',
        'body_en',
        'button_label_ar',
        'button_label_en',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @var array<string, self>|null */
    private static ?array $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        static::$cache = null;
    }

    /** The stored row for one event/channel, or null when it has never been saved. */
    public static function lookup(string $event, string $channel): ?self
    {
        if (static::$cache === null) {
            static::$cache = static::query()
                ->get()
                ->keyBy(fn (self $row) => $row->event . '.' . $row->channel)
                ->all();
        }

        return static::$cache[$event . '.' . $channel] ?? null;
    }

    /** Make sure every event/channel pair has a row, without touching edits. */
    public static function syncMissing(): int
    {
        $created = 0;

        foreach (NotificationTemplates::all() as $row) {
            $exists = static::query()
                ->where('event', $row['event'])
                ->where('channel', $row['channel'])
                ->exists();

            if (! $exists) {
                static::query()->create($row);
                $created++;
            }
        }

        static::flushCache();

        return $created;
    }

    /** Put this row back to the wording the store shipped with. */
    public function resetToDefault(): void
    {
        $this->fill(NotificationTemplates::default($this->event, $this->channel))->save();
    }

    public function isEmail(): bool
    {
        return $this->channel === 'email';
    }

    public function subjectFor(string $locale): ?string
    {
        return $locale === 'ar' ? $this->subject_ar : $this->subject_en;
    }

    public function bodyFor(string $locale): ?string
    {
        return $locale === 'ar' ? $this->body_ar : $this->body_en;
    }

    public function buttonFor(string $locale): ?string
    {
        return $locale === 'ar' ? $this->button_label_ar : $this->button_label_en;
    }
}
